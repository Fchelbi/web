<?php
namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\Formation;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/feedback')]
class FeedbackController extends AbstractController
{
    /**
     * Patient submits feedback on a formation.
     */
    #[Route('/{id}/submit', name: 'app_feedback_submit', methods: ['POST'])]
    public function submit(
        Formation $formation,
        Request $request,
        EntityManagerInterface $em,
        GeminiService $geminiService
    ): Response {
        $comment = trim($request->request->get('comment', ''));
        $rating  = (int) $request->request->get('rating', 0);

        if (strlen($comment) < 5) {
            $this->addFlash('error', 'Le commentaire doit faire au moins 5 caractères.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $feedback = new Feedback();
        $feedback->setFormation($formation);
        $feedback->setUserId(1); // Replace with $this->getUser()->getId_user()
        $feedback->setComment($comment);
        $feedback->setRating($rating > 0 && $rating <= 5 ? $rating : null);

        // AI Sentiment Analysis
        $analysis = $geminiService->analyzeSentiment($comment);
        if ($analysis) {
            $feedback->setSentiment($analysis['sentiment'] ?? null);
            $feedback->setConfidence($analysis['confidence'] ?? null);
            $feedback->setAiSummary($analysis['summary'] ?? null);
        }

        $em->persist($feedback);
        $em->flush();

        $this->addFlash('success', 'Merci pour votre avis ! Sentiment détecté : ' . ucfirst($feedback->getSentiment() ?? 'inconnu'));
        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    /**
     * Coach sentiment dashboard — shows all feedback with sentiment stats.
     */
    #[Route('/dashboard', name: 'app_feedback_dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $coachId = 1; // Replace with $this->getUser()->getId_user()

        // Get formations by this coach
        $formations = $em->getRepository(Formation::class)->findBy(['coachId' => $coachId]);

        $dashboardData = [];
        $totalPositive = 0;
        $totalNegative = 0;
        $totalNeutral  = 0;

        foreach ($formations as $formation) {
            $feedbacks = $em->getRepository(Feedback::class)->findBy(
                ['formation' => $formation],
                ['createdAt' => 'DESC']
            );

            $pos = count(array_filter($feedbacks, fn($f) => $f->getSentiment() === 'positive'));
            $neg = count(array_filter($feedbacks, fn($f) => $f->getSentiment() === 'negative'));
            $neu = count(array_filter($feedbacks, fn($f) => $f->getSentiment() === 'neutral'));

            $totalPositive += $pos;
            $totalNegative += $neg;
            $totalNeutral  += $neu;

            $dashboardData[] = [
                'formation' => $formation,
                'feedbacks' => $feedbacks,
                'positive'  => $pos,
                'negative'  => $neg,
                'neutral'   => $neu,
                'total'     => count($feedbacks),
            ];
        }

        return $this->render('feedback/dashboard.html.twig', [
            'dashboardData' => $dashboardData,
            'totalPositive' => $totalPositive,
            'totalNegative' => $totalNegative,
            'totalNeutral'  => $totalNeutral,
            'totalFeedbacks'=> $totalPositive + $totalNegative + $totalNeutral,
        ]);
    }
}