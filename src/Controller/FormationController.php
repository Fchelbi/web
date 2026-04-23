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
use App\Service\YouTubeService;
use App\Service\GeminiService;
use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Service\RecommendationService;
use App\Service\CertificateService;
use App\Entity\Quiz_result;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\QrCodeService;
use App\Service\WeatherService;
use App\Service\TranslateService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/formation')]
final class FormationController extends AbstractController
{

    // =========================================================================
    // ROLE HELPER — change the return value here to test different roles.
    // When you integrate real authentication, replace the body with:
    //     return $this->getUser()->getRole();
    // and it will automatically work everywhere in this controller.
    // =========================================================================
    private function getUserRole(): string
    {
        return 'Admin'; // ← change to 'Coach' or 'Patient' to test
        // TODO: return $this->getUser()->getRole();
    }

    // =========================================================================
    // INDEX
    // =========================================================================
    #[Route(name: 'app_formation_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        WeatherService $weatherService
    ): Response {
        $role    = $this->getUserRole();
        $perPage = 6;

        if ($role === 'Admin') {
            $query = $entityManager->getRepository(Formation::class)
                ->createQueryBuilder('f')
                ->orderBy('f.id', 'DESC')
                ->getQuery();

            $formations = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                $perPage
            );

            $users    = $entityManager->getRepository(\App\Entity\User::class)->findAll();
            $coachMap = [];
            foreach ($users as $user) {
                $coachMap[$user->getId_user()] = $user->getNom() . ' ' . $user->getPrenom();
            }

            return $this->render('formation/admin_formations.html.twig', [
                'formations' => $formations,
                'coachMap'   => $coachMap,
            ]);

        } elseif ($role === 'Coach') {
            $coachId = 1; // TODO: replace with $this->getUser()->getId_user()

            $query = $entityManager->getRepository(Formation::class)
                ->createQueryBuilder('f')
                ->where('f.coachId = :cid')
                ->setParameter('cid', $coachId)
                ->orderBy('f.id', 'DESC')
                ->getQuery();

            $formations = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                $perPage
            );

            $participations = [];
            foreach ($formations as $formation) {
                $parts = $entityManager->getRepository(Participation::class)
                    ->findBy(['formation_id' => $formation]);
                $participations[$formation->getId()] = $parts;
            }

            return $this->render('formation/coach_formations.html.twig', [
                'formations'     => $formations,
                'participations' => $participations,
            ]);

        } else {
            // Patient
            $query = $entityManager->getRepository(Formation::class)
                ->createQueryBuilder('f')
                ->orderBy('f.id', 'DESC')
                ->getQuery();

            $formations = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                $perPage
            );

            $users    = $entityManager->getRepository(\App\Entity\User::class)->findAll();
            $coachMap = [];
            foreach ($users as $user) {
                $coachMap[$user->getId_user()] = $user->getNom() . ' ' . $user->getPrenom();
            }

            $patientId    = 1; // TODO: replace with $this->getUser()->getId_user()
            $patientUser  = $entityManager->getRepository(\App\Entity\User::class)->find($patientId);
            $myParticipations = $entityManager->getRepository(Participation::class)
                ->findBy(['user_id' => $patientUser]);
            $enrolledIds  = array_map(fn($p) => $p->getFormation_id()->getId(), $myParticipations);
            $weather      = $weatherService->getWeatherWithTip('Tunis');

            return $this->render('formation/patient_formations.html.twig', [
                'formations'  => $formations,
                'enrolledIds' => $enrolledIds,
                'coachMap'    => $coachMap,
                'weather'     => $weather,
            ]);
        }
    }

    // =========================================================================
    // NEW
    // =========================================================================
    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, YouTubeService $youtubeService): Response
    {
        $formation = new Formation();
        $form      = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $videoUrl = $formation->getVideoUrl();
            if ($videoUrl) {
                $details = $youtubeService->getVideoDetails($videoUrl);
                if ($details) {
                    $formation->setVideoUrl($details['embedUrl']);
                    $formation->setVideoTitle($details['title']);
                    $formation->setVideoDuration($details['duration']);
                    $formation->setVideoThumbnail($details['thumbnail']);
                } else {
                    $formation->setVideoUrl($this->normalizeYoutubeUrl($videoUrl));
                }
            }

            $formation->setCoachId(1); // TODO: replace with $this->getUser()->getId_user()

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

    // =========================================================================
    // TRANSLATE (AJAX)
    // =========================================================================
    #[Route('/{id}/translate', name: 'app_formation_translate', methods: ['GET'], priority: 15)]
    public function translate(
        Formation $formation,
        Request $request,
        TranslateService $translateService
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        $lang = $request->query->get('lang', 'en');

        $translatedTitle = $translateService->translate($formation->getTitle(), 'fr', $lang);
        $translatedDesc  = $translateService->translate($formation->getDescription() ?? '', 'fr', $lang);

        return $this->json([
            'title'       => $translatedTitle,
            'description' => $translatedDesc,
            'language'    => $lang,
        ]);
    }

    // =========================================================================
    // YOUTUBE PREVIEW (AJAX)
    // =========================================================================
    #[Route('/youtube/preview', name: 'app_formation_youtube_preview', methods: ['GET'], priority: 20)]
    public function youtubePreview(Request $request, YouTubeService $youtubeService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $url = $request->query->get('url', '');

        if (empty($url)) {
            return $this->json(['error' => 'No URL provided'], 400);
        }

        $details = $youtubeService->getVideoDetails($url);

        if (!$details) {
            return $this->json(['error' => 'Could not fetch video details'], 404);
        }

        return $this->json($details);
    }

    // =========================================================================
    // SHOW  ← role-aware: Admin | Coach | Patient
    // =========================================================================
    

#[Route('/{id}', name: 'app_formation_show', methods: ['GET'])]
public function show(
    Formation $formation,
    EntityManagerInterface $entityManager,
    QrCodeService $qrCodeService,
    Request $request // ✅ ADD THIS
): Response {
    $role = $this->getUserRole();

    $coach     = $entityManager->getRepository(\App\Entity\User::class)->find($formation->getCoachId());
    $coachName = $coach ? $coach->getNom() . ' ' . $coach->getPrenom() : 'Inconnu';
    $quiz      = $entityManager->getRepository(\App\Entity\Quiz::class)->findOneBy(['formation_id' => $formation]);

    $embedUrl = $formation->getVideoUrl()
        ? $this->normalizeYoutubeUrl($formation->getVideoUrl())
        : null;

    // ✅ IMPORTANT: use current host (IP or localhost)
    $host = $request->getSchemeAndHttpHost();

    $formationUrl = $host . $this->generateUrl(
        'app_formation_show',
        ['id' => $formation->getId()]
    );

    $qrCode = $qrCodeService->generateBase64($formationUrl, 180);

    return $this->render('formation/show.html.twig', [
        'formation' => $formation,
        'coachName' => $coachName,
        'quiz'      => $quiz,
        'embedUrl'  => $embedUrl,
        'qrCode'    => $qrCode,
        'role'      => $role,
    ]);
}

    // =========================================================================
    // YOUTUBE SEARCH (AJAX)
    // =========================================================================
    #[Route('/youtube/search', name: 'app_formation_youtube_search', methods: ['GET'], priority: 20)]
    public function youtubeSearch(Request $request, YouTubeService $youtubeService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $query = trim($request->query->get('q', ''));

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $results = $youtubeService->searchVideos($query, 5);

        return $this->json($results);
    }

    // =========================================================================
    // EDIT
    // =========================================================================
    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $entityManager, YouTubeService $youtubeService): Response
    {
        $oldVideoUrl = $formation->getVideoUrl();
        $form        = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $videoUrl = $formation->getVideoUrl();

            if ($videoUrl && $videoUrl !== $oldVideoUrl) {
                $details = $youtubeService->getVideoDetails($videoUrl);
                if ($details) {
                    $formation->setVideoUrl($details['embedUrl']);
                    $formation->setVideoTitle($details['title']);
                    $formation->setVideoDuration($details['duration']);
                    $formation->setVideoThumbnail($details['thumbnail']);
                } else {
                    $formation->setVideoUrl($this->normalizeYoutubeUrl($videoUrl));
                }
            } elseif (!$videoUrl) {
                $formation->setVideoTitle(null);
                $formation->setVideoDuration(null);
                $formation->setVideoThumbnail(null);
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

    // =========================================================================
    // TEST GEMINI
    // =========================================================================
    #[Route('/test/gemini', name: 'app_test_gemini', methods: ['GET'], priority: 20)]
    public function testGemini(\App\Service\GeminiService $geminiService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $result = $geminiService->generateQuizQuestions('Nutrition et bien-être', 'Formation sur les bases de la nutrition santé', 2);

        if ($result === null) {
            return $this->json(['error' => 'Gemini returned null — API key or connection issue']);
        }

        return $this->json($result);
    }

    // =========================================================================
    // GENERATE QUIZ WITH AI
    // =========================================================================
    #[Route('/{id}/quiz/generate', name: 'app_formation_quiz_generate', methods: ['POST'], priority: 15)]
    public function generateQuiz(
        Formation $formation,
        EntityManagerInterface $entityManager,
        GeminiService $geminiService,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('generate_quiz' . $formation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_formation_quiz', ['id' => $formation->getId()]);
        }

        $quiz = $entityManager->getRepository(Quiz::class)
            ->findOneBy(['formation_id' => $formation]);

        if (!$quiz) {
            $quiz = new Quiz();
            $quiz->setFormation_id($formation);
            $quiz->setTitle('Quiz : ' . $formation->getTitle());
            $quiz->setPassingScore(60);
            $entityManager->persist($quiz);
        }

        $generated = $geminiService->generateQuizQuestions(
            $formation->getTitle(),
            $formation->getDescription()
        );

        if (!$generated) {
            $this->addFlash('error', 'Erreur lors de la génération IA. Réessayez.');
            return $this->redirectToRoute('app_formation_quiz', ['id' => $formation->getId()]);
        }

        foreach ($quiz->getQuestions() as $existingQ) {
            $quiz->removeQuestion($existingQ);
            $entityManager->remove($existingQ);
        }

        foreach ($generated as $qData) {
            $question = new Question();
            $question->setQuestionText($qData['question']);
            $question->setPoints($qData['points'] ?? 1);
            $question->setQuiz($quiz);

            foreach ($qData['answers'] as $aData) {
                $reponse = new Reponse();
                $reponse->setOptionText($aData['text']);
                $reponse->setIsCorrect($aData['correct'] ?? false);
                $reponse->setQuestion($question);
                $question->addReponse($reponse);
            }

            $quiz->addQuestion($question);
        }

        $entityManager->flush();

        $this->addFlash('success', count($generated) . ' questions générées par IA avec succès !');
        return $this->redirectToRoute('app_formation_quiz', ['id' => $formation->getId()]);
    }

    // =========================================================================
    // DELETE
    // =========================================================================
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

    // =========================================================================
    // ENROLL (Patient)
    // =========================================================================
    #[Route('/{id}/enroll', name: 'app_formation_enroll', methods: ['POST'])]
    public function enroll(Formation $formation, EntityManagerInterface $entityManager): Response
    {
        $patientId   = 6; // TODO: replace with $this->getUser()->getId_user()
        $patientUser = $entityManager->getRepository(\App\Entity\User::class)->find($patientId);

        if (!$patientUser) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_formation_index');
        }

        $existing = $entityManager->getRepository(Participation::class)->findOneBy([
            'user_id'      => $patientUser,
            'formation_id' => $formation,
        ]);

        if (!$existing) {
            $participation = new Participation();
            $participation->setUser_id($patientUser);
            $participation->setFormation_id($formation);
            $participation->setDate_inscription(new \DateTime());
            $entityManager->persist($participation);
            $entityManager->flush();
            $this->addFlash('success', 'Inscription réussie !');
        } else {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cette formation.');
        }

        return $this->redirectToRoute('app_formation_index');
    }

   // =========================================================================
// MANAGE QUIZ (Admin / Coach)
// =========================================================================
#[Route('/{id}/quiz', name: 'app_formation_quiz', methods: ['GET'])]
public function manageQuiz(
    Formation $formation,
    EntityManagerInterface $entityManager,
    QrCodeService $qrCodeService
): Response {
    $quiz = $entityManager->getRepository(\App\Entity\Quiz::class)
        ->findOneBy(['formation_id' => $formation]);

    $coach     = $entityManager->getRepository(\App\Entity\User::class)->find($formation->getCoachId());
    $coachName = $coach ? $coach->getNom() . ' ' . $coach->getPrenom() : 'Inconnu';

    $videoUrl = $formation->getVideoUrl();
    $embedUrl = $videoUrl ? $this->normalizeYoutubeUrl($videoUrl) : null;  // ← always defined

    $formationUrl = $this->generateUrl(
        'app_formation_show',
        ['id' => $formation->getId()],
        \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
    );
    $qrCode = $qrCodeService->generateBase64($formationUrl, 180);

    return $this->render('formation/quiz.html.twig', [
        'formation' => $formation,
        'quiz'      => $quiz,
        'role'      => $this->getUserRole(),
        'coachName' => $coachName,
        'embedUrl'  => $embedUrl,
        'qrCode'    => $qrCode,
    ]);
}

    // =========================================================================
    // AJAX SEARCH
    // =========================================================================
    #[Route('/search/ajax', name: 'app_formation_search', methods: ['GET'], priority: 10)]
    public function ajaxSearch(Request $request, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $qb         = $entityManager->createQueryBuilder();
        $formations = $qb->select('f')
            ->from(Formation::class, 'f')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(f.title)', ':search'),
                    $qb->expr()->like('LOWER(f.description)', ':search'),
                    $qb->expr()->like('LOWER(f.category)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($q) . '%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $users    = $entityManager->getRepository(\App\Entity\User::class)->findAll();
        $coachMap = [];
        foreach ($users as $user) {
            $coachMap[$user->getId_user()] = $user->getNom() . ' ' . $user->getPrenom();
        }

        $data = [];
        foreach ($formations as $f) {
            $data[] = [
                'id'             => $f->getId(),
                'title'          => $f->getTitle(),
                'category'       => $f->getCategory() ?? '—',
                'description'    => mb_substr($f->getDescription() ?? '', 0, 50),
                'coach'          => ($f->getCoachId() && isset($coachMap[$f->getCoachId()])) ? $coachMap[$f->getCoachId()] : '—',
                'hasVideo'       => $f->getVideoUrl() !== null,
                'hasQuiz'        => $f->getQuizs()->count() > 0,
                'videoThumbnail' => $f->getVideoThumbnail(),
                'videoDuration'  => $f->getVideoDuration(),
            ];
        }

        return $this->json($data);
    }

    // =========================================================================
    // DOWNLOAD CERTIFICATE (Patient)
    // =========================================================================
    #[Route('/{id}/certificate', name: 'app_formation_certificate', methods: ['GET'], priority: 15)]
    public function downloadCertificate(
        Formation $formation,
        EntityManagerInterface $entityManager,
        CertificateService $certificateService
    ): Response {
        $userId = 1; // TODO: replace with $this->getUser()->getId_user()

        $quiz = $entityManager->getRepository(\App\Entity\Quiz::class)
            ->findOneBy(['formation_id' => $formation]);

        if (!$quiz) {
            $this->addFlash('error', 'Aucun quiz pour cette formation.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $result = $entityManager->getRepository(Quiz_result::class)
            ->findOneBy(
                ['quiz_id' => $quiz, 'user_id' => $userId, 'passed' => true],
                ['score' => 'DESC']
            );

        if (!$result) {
            $this->addFlash('error', 'Vous devez d\'abord réussir le quiz pour obtenir le certificat.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $user        = $entityManager->getRepository(\App\Entity\User::class)->find($userId);
        $studentName = $user ? $user->getNom() . ' ' . $user->getPrenom() : 'Participant';

        $pdfContent = $certificateService->generateCertificate(
            $studentName,
            $formation->getTitle(),
            $result->getScore(),
            $result->getTotal_points(),
            $result->getCompleted_at()
        );

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certificat-' . $formation->getId() . '.pdf"',
        ]);
    }

    // =========================================================================
    // PRIVATE HELPER — normalize any YouTube URL to embed format
    // =========================================================================
    private function normalizeYoutubeUrl(string $url): string
    {
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        $videoId = null;

        if (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $m)) {
            $videoId = $m[1];
        } elseif (preg_match('#[?&]v=([a-zA-Z0-9_-]{11})#', $url, $m)) {
            $videoId = $m[1];
        } elseif (preg_match('#youtube\.com/shorts/([a-zA-Z0-9_-]{11})#', $url, $m)) {
            $videoId = $m[1];
        }

        if ($videoId) {
            return 'https://www.youtube.com/embed/' . $videoId;
        }

        return $url;
    }
    #[Route('/{id}/chat', name: 'app_formation_chat', methods: ['POST'], priority: 15)]
    public function chat(
        Formation $formation,
        \Symfony\Component\HttpFoundation\Request $request,
        \App\Service\GeminiService $geminiService
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        $body    = json_decode($request->getContent(), true);
        $message = trim($body['message'] ?? '');
        $history = $body['history'] ?? [];
 
        if (!$message) {
            return $this->json(['reply' => 'Veuillez écrire un message.']);
        }
 
        $reply = $geminiService->chatAboutFormation(
            $formation->getTitle(),
            $formation->getDescription(),
            $formation->getCategory(),
            $message,
            $history
        );
 
        return $this->json([
            'reply' => $reply ?? 'Je ne peux pas répondre pour le moment. Réessayez.',
        ]);
    }
    
    
}
