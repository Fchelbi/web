<?php
namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Participation;
use App\Entity\Quiz;
use App\Entity\Quiz_result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticsController extends AbstractController
{
    #[Route('/statistics', name: 'app_statistics', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // 1. Popular formations (by enrollment count)
        $formations = $em->getRepository(Formation::class)->findAll();
        $popularData = [];
        foreach ($formations as $f) {
            $count = $em->getRepository(Participation::class)
                ->count(['formation_id' => $f]);
            $popularData[] = [
                'title' => $f->getTitle(),
                'count' => $count,
            ];
        }
        usort($popularData, fn($a, $b) => $b['count'] <=> $a['count']);
        $popularData = array_slice($popularData, 0, 10);

        // 2. Quiz success rate per formation
        $quizData = [];
        $quizzes = $em->getRepository(Quiz::class)->findAll();
        foreach ($quizzes as $quiz) {
            $results = $em->getRepository(Quiz_result::class)->findBy(['quiz_id' => $quiz]);
            $total = count($results);
            $passed = count(array_filter($results, fn($r) => $r->getPassed()));
            $quizData[] = [
                'title'  => $quiz->getFormation_id() ? $quiz->getFormation_id()->getTitle() : $quiz->getTitle(),
                'total'  => $total,
                'passed' => $passed,
                'rate'   => $total > 0 ? round(($passed / $total) * 100) : 0,
            ];
        }

        // 3. Enrollments over time (last 12 months)
        $conn = $em->getConnection();
        $enrollmentSql = "
            SELECT DATE_FORMAT(date_inscription, '%Y-%m') as month,
                   COUNT(*) as count
            FROM participation
            WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ";
        $enrollmentData = $conn->executeQuery($enrollmentSql)->fetchAllAssociative();

        // 4. Category distribution
        $categorySql = "
            SELECT COALESCE(category, 'Non classé') as cat,
                   COUNT(*) as count
            FROM formation
            GROUP BY cat
            ORDER BY count DESC
        ";
        $categoryData = $conn->executeQuery($categorySql)->fetchAllAssociative();

        // 5. General stats
        $totalFormations    = $em->getRepository(Formation::class)->count([]);
        $totalParticipations = $em->getRepository(Participation::class)->count([]);
        $totalQuizResults   = $em->getRepository(Quiz_result::class)->count([]);
        $totalPassed        = count($em->getRepository(Quiz_result::class)->findBy(['passed' => true]));

        return $this->render('statistics/index.html.twig', [
            'popularData'        => $popularData,
            'quizData'           => $quizData,
            'enrollmentData'     => $enrollmentData,
            'categoryData'       => $categoryData,
            'totalFormations'    => $totalFormations,
            'totalParticipations'=> $totalParticipations,
            'totalQuizResults'   => $totalQuizResults,
            'totalPassed'        => $totalPassed,
        ]);
    }
}