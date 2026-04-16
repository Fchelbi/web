<?php

namespace App\Controller;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use App\Form\ConsultationFilterType;
use App\Form\ConsultationGestionType;
use App\Form\ConsultationType;
use App\Repository\ConsultationEnLigneRepository;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConsultationController extends AbstractController
{
    #[Route('/consultations', name: 'consultation_list', methods: ['GET'])]
    public function index(
        Request $request,
        ConsultationEnLigneRepository $repository,
        PaginatorInterface $paginator
    ): Response
    {
        $counts = $repository->getStatutCounts();

        $filterForm = $this->createForm(ConsultationFilterType::class, null, [
            'method' => 'GET',
        ]);
        $filterForm->handleRequest($request);

        $statut = null;
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();
            $statut = $data['statut'] ?? null;
        }

        $queryBuilder = $repository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->leftJoin('c.psychologue', 'p')
            ->addSelect('p')
            ->orderBy('c.dateConsultation', 'ASC');

        if ($statut !== null && $statut !== '') {
            $queryBuilder
                ->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

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
                    $consultation->setUser($this->getOrCreateUser($entityManager));
                    $entityManager->persist($consultation);
                    $entityManager->flush();

                    $this->addFlash('success', 'Votre demande de consultation a ete envoyee avec succes.');

                    return $this->redirectToRoute('consultation_list');
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
    public function psyList(ConsultationEnLigneRepository $repository): Response
    {
        return $this->render('consultation/psy.html.twig', [
            'consultations' => $repository->findByStatut(null),
        ]);
    }

    #[Route('/psy/accept/{id}', name: 'psy_accept', methods: ['POST'])]
    public function accept(
        ConsultationEnLigne $consultation,
        EntityManagerInterface $entityManager,
        SmsService $smsService
    ): Response {
        $consultation->setStatut(ConsultationEnLigne::STATUT_CONFIRMEE);
        $entityManager->flush();

        $message = sprintf(
            'Votre consultation du %s est confirmee.%s',
            $consultation->getDateConsultation()?->format('d/m/Y H:i') ?? '',
            $consultation->getMeetLink() ? ' Lien Meet : ' . $consultation->getMeetLink() : ''
        );

        if (!$smsService->send($consultation->getUser()?->getNumTel(), $message)) {
            $this->addFlash('error', 'La consultation est confirmee, mais le SMS n a pas pu etre envoye.');
        }

        $this->addFlash('success', 'La consultation a ete confirmee.');

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
            'Votre consultation du %s doit etre replanifiee. Merci de choisir une nouvelle date.',
            $consultation->getDateConsultation()?->format('d/m/Y H:i') ?? ''
        );

        if (!$smsService->send($consultation->getUser()?->getNumTel(), $message)) {
            $this->addFlash('error', 'La consultation est annulee, mais le SMS n a pas pu etre envoye.');
        }

        $this->addFlash('success', 'La consultation a ete annulee.');

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

    private function getOrCreateUser(EntityManagerInterface $entityManager): User
    {
        $repository = $entityManager->getRepository(User::class);
        $user = $repository->findOneBy(['role' => User::ROLE_PATIENT]) ?? $repository->find(1);

        if ($user instanceof User) {
            return $user;
        }

        $user = new User();
        $user
            ->setNom('Patient')
            ->setPrenom('Demo')
            ->setEmail('patient.demo@example.com')
            ->setMdp('1234')
            ->setRole(User::ROLE_PATIENT)
            ->setNumTel('00000000');

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
