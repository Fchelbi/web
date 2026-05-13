<?php

namespace App\Controller;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use App\Form\ConsultationFilterType;
use App\Form\ConsultationGestionType;
use App\Form\ConsultationType;
use App\Repository\ConsultationEnLigneRepository;
use App\Service\AiService;
use App\Service\GoogleMeetService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConsultationController extends AbstractController
{
    #[Route('/google/connect', name: 'google_connect', methods: ['GET'])]
    public function googleConnect(GoogleMeetService $googleMeetService): Response
    {
        return $this->redirect($googleMeetService->getAuthorizationUrl());
    }

    #[Route('/google/callback', name: 'google_callback', methods: ['GET'])]
    public function googleCallback(Request $request, GoogleMeetService $googleMeetService): Response
    {
        $code = $request->query->get('code');

        if (!$code) {
            $this->addFlash('error', 'Autorisation Google annulee.');

            return $this->redirectToRoute('psy_consultations');
        }

        try {
            $googleMeetService->saveTokenFromCode($code);
            $this->addFlash('success', 'Google Calendar est connecte.');
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Connexion Google impossible : ' . $exception->getMessage());
        }

        return $this->redirectToRoute('psy_consultations');
    }

    #[Route('/ai/suggest', name: 'ai_suggest', methods: ['POST'])]
    public function aiSuggest(Request $request, AiService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            $data = $request->request->all();
        }

        $motif = (string) ($data['motif'] ?? '');

        try {
            return $this->json([
                'success' => true,
                'suggestion' => $aiService->suggestPsychologue($motif),
            ]);
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/consultations', name: 'consultation_list', methods: ['GET'])]
    public function index(
        Request $request,
        ConsultationEnLigneRepository $repository,
        PaginatorInterface $paginator
    ): Response
    {
        $currentUser = $this->getUser();
        $isPatient = $currentUser && in_array(User::ROLE_PATIENT, $currentUser->getRoles());
        
        $counts = $isPatient 
            ? $repository->getStatutCountsByUser($currentUser)
            : $repository->getStatutCounts();

        $filterForm = $this->createForm(ConsultationFilterType::class, null, [
            'method' => 'GET',
        ]);
        $filterForm->handleRequest($request);

        $statut = null;
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();
            $statut = $data['statut'] ?? null;
        }

        $queryBuilder = $isPatient
            ? $repository->createPatientQueryBuilder($currentUser, $statut)
            : $repository->createListQueryBuilder($statut);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('consultation/index.html.twig', [
            'consultations' => $pagination,
            'pagination' => $pagination,
            'counts' => $counts,
            'countEnAttente' => $counts[ConsultationEnLigne::STATUT_EN_ATTENTE] ?? 0,
            'countConfirmee' => $counts[ConsultationEnLigne::STATUT_CONFIRMEE] ?? 0,
            'countAnnulee' => $counts[ConsultationEnLigne::STATUT_ANNULEE] ?? 0,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/consultation/add/{psychologue}', name: 'consultation_add_with_psychologue', methods: ['GET', 'POST'], requirements: ['psychologue' => '\d+'])]
    #[Route('/consultation/add', name: 'consultation_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        ConsultationEnLigneRepository $repository,
        ?User $psychologue = null
    ): Response {
        if ($psychologue !== null && $psychologue->getRole() !== User::ROLE_COACH) {
            throw $this->createNotFoundException('Psychologue introuvable.');
        }

        $consultation = new ConsultationEnLigne();
        $consultation->setStatut(ConsultationEnLigne::STATUT_EN_ATTENTE);
        $consultation->setPsychologue($psychologue);

        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($repository->isDateAlreadyUsed($consultation->getDateConsultation())) {
                    $form->get('dateConsultation')->addError(new FormError('Ce creneau est deja reserve.'));
                } else {
                    $consultation->setUser($this->getAuthenticatedPatient());
                    $entityManager->persist($consultation);
                    $entityManager->flush();

                    $this->addFlash('success', 'Votre demande de consultation a ete envoyee avec succes.');

                    return $this->redirectToRoute('patient_dashboard');
                }
            } else {
                $this->addFlash('error', 'Merci de corriger les erreurs du formulaire.');
            }
        }

        return $this->render('consultation/add.html.twig', [
            'form' => $form->createView(),
            'psychologueSelectionne' => $psychologue,
        ]);
    }

    #[Route('/consultation/delete/{id}', name: 'consultation_delete', methods: ['POST'])]
    public function delete(
        ConsultationEnLigne $consultation,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($consultation);
        $entityManager->flush();

        $this->addFlash('success', 'La consultation a ete supprimee.');

        return $this->redirectToRoute('consultation_list');
    }

    #[Route('/psy/consultations', name: 'psy_consultations', methods: ['GET'])]
    public function psyList(
        Request $request,
        ConsultationEnLigneRepository $repository,
        PaginatorInterface $paginator
    ): Response
    {
        $pagination = $paginator->paginate(
            $repository->createListQueryBuilder(null),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('consultation/psy.html.twig', [
            'consultations' => $pagination,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/psy/accept/{id}', name: 'psy_accept', methods: ['POST'])]
    public function accept(
        ConsultationEnLigne $consultation,
        EntityManagerInterface $entityManager,
        GoogleMeetService $googleMeetService,
        SmsService $smsService
    ): Response {
        $consultation->setStatut(ConsultationEnLigne::STATUT_CONFIRMEE);

        $meetLinkCreated = false;
        try {
            $meetLink = $googleMeetService->createMeetLink($consultation);
            $consultation->setMeetLink($meetLink);
            $meetLinkCreated = true;
        } catch (\Throwable $exception) {
            $consultation->setMeetLink(null);
            $this->logError(sprintf('Google Meet creation failed for consultation %d: %s', $consultation->getId(), $exception->getMessage()));
            $this->addFlash('warning', 'Consultation confirmée, mais le lien Meet n\'a pas pu être généré. Un lien alternatif a été créé.');
        }

        $entityManager->flush();

        $message = sprintf(
            'Votre consultation du %s est confirmée.%s',
            $consultation->getDateConsultation()?->format('d/m/Y H:i') ?? '',
            $consultation->getMeetLink() ? ' Lien Meet : ' . $consultation->getMeetLink() : ''
        );

        $smsResult = $smsService->send($consultation->getUser()?->getNumTel(), $message);
        if (!$smsResult) {
            $this->logError(sprintf('SMS notification failed for consultation %d (patient: %s)', $consultation->getId(), $consultation->getUser()?->getNumTel() ?? 'NO_PHONE'));
            $this->addFlash('warning', 'Consultation confirmée, mais le SMS n\'a pas pu être envoyé au patient.');
        }

        $this->addFlash('success', 'La consultation a été confirmée.');

        return $this->redirectToRoute('psy_consultations');
    }

    #[Route('/psy/cancel/{id}', name: 'psy_cancel', methods: ['POST'])]
    public function cancel(
        ConsultationEnLigne $consultation,
        EntityManagerInterface $entityManager,
        SmsService $smsService
    ): Response {
        $consultation->setStatut(ConsultationEnLigne::STATUT_ANNULEE);
        $consultation->setMeetLink(null);
        $entityManager->flush();

        $message = sprintf(
            'Votre consultation du %s doit être replanifiée. Merci de choisir une nouvelle date.',
            $consultation->getDateConsultation()?->format('d/m/Y H:i') ?? ''
        );

        $smsResult = $smsService->send($consultation->getUser()?->getNumTel(), $message);
        if (!$smsResult) {
            error_log(sprintf('[%s] SMS cancellation notification failed for consultation %d (patient: %s)', date('Y-m-d H:i:s'), $consultation->getId(), $consultation->getUser()?->getNumTel() ?? 'NO_PHONE'), 3, 'var/log/consultation_errors.log');
            $this->addFlash('warning', 'Consultation annulée, mais le SMS de notification n\'a pas pu être envoyé au patient.');
        }

        $this->addFlash('success', 'La consultation a été annulée.');

        return $this->redirectToRoute('psy_consultations');
    }

    #[Route('/consultation/edit/{id}', name: 'consultation_edit', methods: ['GET', 'POST'])]
    public function edit(
        ConsultationEnLigne $consultation,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ConsultationGestionType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if (
                    $consultation->getStatut() === ConsultationEnLigne::STATUT_CONFIRMEE
                    && $consultation->getMeetLink() === null
                ) {
                    $form->get('meetLink')->addError(new FormError('Ajoutez un lien Meet pour une consultation confirmee.'));
                } else {
                    $entityManager->flush();

                    $this->addFlash('success', 'La consultation a ete mise a jour.');

                    return $this->redirectToRoute('psy_consultations');
                }
            } else {
                $this->addFlash('error', 'Merci de corriger les erreurs du formulaire.');
            }
        }

        return $this->render('consultation/edit.html.twig', [
            'consultation' => $consultation,
            'form' => $form->createView(),
        ]);
    }

    private function getAuthenticatedPatient(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        return $user;
    }

    private function logError(string $message): void
    {
        $logDir = $this->getParameter('kernel.project_dir') . '/var/log';
        
        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        
        $logFile = $logDir . '/consultation_errors.log';
        $logEntry = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
