<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Participation;
use App\Form\FormationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formation')]
final class FormationController extends AbstractController
{
    #[Route(name: 'app_formation_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Hardcode role for testing — replace with $this->getUser()->getRole() later
        $role = 'Admin'; // Change to 'Coach', 'Admin', or 'Patient' to test

        // --- Pagination setup ---
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = 6;

        if ($role === 'Admin') {
            $repo       = $entityManager->getRepository(Formation::class);
            $total      = count($repo->findAll());
            $formations = $repo->findBy([], null, $perPage, ($page - 1) * $perPage);
            $totalPages = (int) ceil($total / $perPage);

            $users    = $entityManager->getRepository(\App\Entity\User::class)->findAll();
            $coachMap = [];
            foreach ($users as $user) {
                $coachMap[$user->getId_user()] = $user->getNom() . ' ' . $user->getPrenom();
            }

            return $this->render('formation/admin_formations.html.twig', [
                'formations' => $formations,
                'coachMap'   => $coachMap,
                'page'       => $page,
                'totalPages' => $totalPages,
                'total'      => $total,
            ]);

        } elseif ($role === 'Coach') {
            $coachId    = 1; // Replace with $this->getUser()->getId_user() later
            $repo       = $entityManager->getRepository(Formation::class);
            $total      = count($repo->findBy(['coachId' => $coachId]));
            $formations = $repo->findBy(['coachId' => $coachId], null, $perPage, ($page - 1) * $perPage);
            $totalPages = (int) ceil($total / $perPage);

            $participations = [];
            foreach ($formations as $formation) {
                $parts = $entityManager->getRepository(Participation::class)
                    ->findBy(['formation_id' => $formation]);
                $participations[$formation->getId()] = $parts;
            }

            return $this->render('formation/coach_formations.html.twig', [
                'formations'    => $formations,
                'participations'=> $participations,
                'page'          => $page,
                'totalPages'    => $totalPages,
                'total'         => $total,
            ]);

        } else {
            // Patient
            $repo       = $entityManager->getRepository(Formation::class);
            $total      = count($repo->findAll());
            $formations = $repo->findBy([], null, $perPage, ($page - 1) * $perPage);
            $totalPages = (int) ceil($total / $perPage);

            $users    = $entityManager->getRepository(\App\Entity\User::class)->findAll();
            $coachMap = [];
            foreach ($users as $user) {
                $coachMap[$user->getId_user()] = $user->getNom() . ' ' . $user->getPrenom();
            }

            $patientId        = 1; // Replace with $this->getUser()->getId_user() later
            $patientUser      = $entityManager->getRepository(\App\Entity\User::class)->find($patientId);
            $myParticipations = $entityManager->getRepository(Participation::class)
                ->findBy(['user_id' => $patientUser]);
            $enrolledIds      = array_map(fn($p) => $p->getFormation_id()->getId(), $myParticipations);

            return $this->render('formation/patient_formations.html.twig', [
                'formations' => $formations,
                'enrolledIds'=> $enrolledIds,
                'coachMap'   => $coachMap,
                'page'       => $page,
                'totalPages' => $totalPages,
                'total'      => $total,
            ]);
        }
    }

    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = new Formation();
        $form      = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Normalize YouTube URL to embed format if needed
            $videoUrl = $formation->getVideoUrl();
            if ($videoUrl) {
                $formation->setVideoUrl($this->normalizeYoutubeUrl($videoUrl));
            }

            // Set coach id — replace with $this->getUser()->getId_user() later
            $formation->setCoachId(1);

            $entityManager->persist($formation);
            $entityManager->flush();
            $this->addFlash('success', 'Formation créée avec succès !');
            return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('formation/new.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_formation_show', methods: ['GET'])]
    public function show(Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $coach     = $entityManager->getRepository(\App\Entity\User::class)->find($formation->getCoachId());
        $coachName = $coach ? $coach->getNom() . ' ' . $coach->getPrenom() : 'Inconnu';
        $quiz      = $entityManager->getRepository(\App\Entity\Quiz::class)->findOneBy(['formation_id' => $formation]);

        // Build embeddable video URL
        $embedUrl = $formation->getVideoUrl()
            ? $this->normalizeYoutubeUrl($formation->getVideoUrl())
            : null;

        return $this->render('formation/show.html.twig', [
            'formation' => $formation,
            'coachName' => $coachName,
            'quiz'      => $quiz,
            'embedUrl'  => $embedUrl,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $videoUrl = $formation->getVideoUrl();
            if ($videoUrl) {
                $formation->setVideoUrl($this->normalizeYoutubeUrl($videoUrl));
            }
            $entityManager->flush();
            $this->addFlash('success', 'Formation modifiée avec succès !');
            return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('formation/edit.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();
            $this->addFlash('success', 'Formation supprimée.');
        }
        return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
    }

    // Patient enrolls in a formation
    #[Route('/{id}/enroll', name: 'app_formation_enroll', methods: ['POST'])]
    public function enroll(Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $patientId   = 1; // Replace with $this->getUser()->getId_user() later
        $patientUser = $entityManager->getRepository(\App\Entity\User::class)->find($patientId);

        if (!$patientUser) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_formation_index');
        }

        // Check if already enrolled
        $existing = $entityManager->getRepository(Participation::class)->findOneBy([
            'user_id'      => $patientUser,
            'formation_id' => $formation,
        ]);

        if (!$existing) {
            $participation = new Participation();
            $participation->setUser_id($patientUser);        // Pass the User entity, not an int
            $participation->setFormation_id($formation);     // Pass the Formation entity
            $entityManager->persist($participation);
            $entityManager->flush();
            $this->addFlash('success', 'Inscription réussie !');
        } else {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cette formation.');
        }

        return $this->redirectToRoute('app_formation_index');
    }

    // -------------------------------------------------------------------------
    // Quiz management (admin)
    // -------------------------------------------------------------------------
    #[Route('/{id}/quiz', name: 'app_formation_quiz', methods: ['GET'])]
    public function manageQuiz(Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $quiz = $entityManager->getRepository(\App\Entity\Quiz::class)
            ->findOneBy(['formation_id' => $formation]);

        return $this->render('formation/quiz.html.twig', [
            'formation' => $formation,
            'quiz'      => $quiz,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper: convert any YouTube URL → embed URL
    // Accepts: youtu.be/ID, watch?v=ID, /shorts/ID, already embed URLs
    // -------------------------------------------------------------------------
    private function normalizeYoutubeUrl(string $url): string
    {
        // Already an embed URL
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        $videoId = null;

        // youtu.be/VIDEO_ID
        if (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $m)) {
            $videoId = $m[1];
        }
        // youtube.com/watch?v=VIDEO_ID
        elseif (preg_match('#[?&]v=([a-zA-Z0-9_-]{11})#', $url, $m)) {
            $videoId = $m[1];
        }
        // youtube.com/shorts/VIDEO_ID
        elseif (preg_match('#youtube\.com/shorts/([a-zA-Z0-9_-]{11})#', $url, $m)) {
            $videoId = $m[1];
        }

        if ($videoId) {
            return 'https://www.youtube.com/embed/' . $videoId;
        }

        // Not a YouTube URL — return as-is (e.g. Vimeo)
        return $url;
    }
}
