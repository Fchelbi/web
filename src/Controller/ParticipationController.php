<?php
// ============================================================
// NEW: ParticipationController.php
// Required so the coach sidebar "Mes Patients" link works.
// Route: GET /participation/          → app_participation_index
// Lists all participations for the logged-in coach's formations.
// ============================================================

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/participation')]
final class ParticipationController extends AbstractController
{
    // Same role helper pattern as FormationController.
    // Replace with $this->getUser()->getRole() when auth is integrated.
    private function getUserRole(): string
    {
        return 'Coach'; // ← change to test
    }

    // =========================================================================
    // INDEX — list all patients enrolled in this coach's formations
    // =========================================================================
    #[Route('/', name: 'app_participation_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {
        $coachId = 1; // TODO: replace with $this->getUser()->getId_user()

        // Fetch all formations that belong to this coach
        $formations = $em->getRepository(Formation::class)
            ->findBy(['coachId' => $coachId]);

        $formationIds = array_map(fn($f) => $f->getId(), $formations);

        // Build a paginated list of participations for those formations
        $qb = $em->createQueryBuilder()
            ->select('p')
            ->from(Participation::class, 'p')
            ->join('p.formation_id', 'f')
            ->join('p.user_id', 'u')
            ->where('f.coachId = :coachId')
            ->setParameter('coachId', $coachId)
            ->orderBy('p.date_inscription', 'DESC');

        $participations = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            15
        );

        // Build a formation map for display (id → title)
        $formationMap = [];
        foreach ($formations as $f) {
            $formationMap[$f->getId()] = $f->getTitle();
        }

        return $this->render('participation/index.html.twig', [
            'participations' => $participations,
            'formationMap'   => $formationMap,
            'role'           => $this->getUserRole(),
        ]);
    }

    // =========================================================================
    // UNENROLL — coach can remove a patient from one of their formations
    // =========================================================================
    #[Route('/{id}/delete', name: 'app_participation_delete', methods: ['POST'])]
    public function delete(
        Participation $participation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete_participation_' . $participation->getId(), $request->request->get('_token'))) {
            $em->remove($participation);
            $em->flush();
            $this->addFlash('success', 'Patient désincrit avec succès.');
        }
        return $this->redirectToRoute('app_participation_index');
    }

    #[Route('/results/{userId}/{participationId}', name: 'app_participation_results', methods: ['GET'])]
    public function results(
        int $userId,
        int $participationId,
        \Doctrine\ORM\EntityManagerInterface $em
    ): \Symfony\Component\HttpFoundation\JsonResponse {
 
        // Find the participation to get the formation → quiz
        $participation = $em->getRepository(\App\Entity\Participation::class)->find($participationId);
 
        if (!$participation) {
            return $this->json([]);
        }
 
        $formation = $participation->getFormation_id();
        $quiz = $em->getRepository(\App\Entity\Quiz::class)
            ->findOneBy(['formation_id' => $formation]);
 
        if (!$quiz) {
            return $this->json([]);
        }
 
        // Get all quiz results for this user on this quiz
        $results = $em->getRepository(\App\Entity\Quiz_result::class)
            ->findBy(
                ['quiz_id' => $quiz, 'user_id' => $userId],
                ['completed_at' => 'DESC']
            );
 
        $data = [];
        foreach ($results as $r) {
            $data[] = [
                'score'  => $r->getScore(),
                'total'  => $r->getTotal_points(),
                'passed' => $r->getPassed(),
                'date'   => $r->getCompleted_at()->format('d/m/Y H:i'),
            ];
        }
 
        return $this->json($data);
    }
 
}
