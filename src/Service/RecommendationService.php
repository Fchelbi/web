<?php
namespace App\Service;

use App\Entity\Formation;
use App\Entity\Participation;
use App\Entity\Quiz_result;
use Doctrine\ORM\EntityManagerInterface;

class RecommendationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Get recommended formations for a patient.
     * Logic:
     * 1. Find categories the patient has enrolled in
     * 2. Weight categories by enrollment count + quiz passes
     * 3. Recommend un-enrolled formations from preferred categories
     * 4. Diversify with 1-2 formations from other categories
     *
     * Returns array of Formation entities, max $limit items.
     */
    public function getRecommendations(int $userId, int $limit = 6): array
    {
        // Get user's participations
        $participations = $this->em->getRepository(Participation::class)
            ->createQueryBuilder('p')
            ->join('p.formation_id', 'f')
            ->where('p.user_id = :uid')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getResult();

        $enrolledFormationIds = [];
        $categoryScores = [];

        foreach ($participations as $p) {
            $formation = $p->getFormation_id();
            $enrolledFormationIds[] = $formation->getId();
            $cat = $formation->getCategory();
            if ($cat) {
                $categoryScores[$cat] = ($categoryScores[$cat] ?? 0) + 1;
            }
        }

        // Boost categories where patient passed quizzes
        $quizResults = $this->em->getRepository(Quiz_result::class)
            ->createQueryBuilder('qr')
            ->join('qr.quiz_id', 'q')
            ->join('q.formation_id', 'f')
            ->where('qr.user_id = :uid')
            ->andWhere('qr.passed = true')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getResult();

        foreach ($quizResults as $qr) {
            $cat = $qr->getQuiz_id()->getFormation_id()->getCategory();
            if ($cat) {
                $categoryScores[$cat] = ($categoryScores[$cat] ?? 0) + 2; // Extra weight for passed quizzes
            }
        }

        // Sort categories by score (highest first)
        arsort($categoryScores);

        // Get all formations NOT enrolled
        $qb = $this->em->getRepository(Formation::class)->createQueryBuilder('f');

        if (!empty($enrolledFormationIds)) {
            $qb->where('f.id NOT IN (:enrolled)')
               ->setParameter('enrolled', $enrolledFormationIds);
        }

        $availableFormations = $qb->getQuery()->getResult();

        if (empty($availableFormations)) {
            return [];
        }

        // Score each available formation
        $scored = [];
        foreach ($availableFormations as $f) {
            $cat = $f->getCategory();
            $score = $categoryScores[$cat] ?? 0;

            // Small bonus if it has video or quiz
            if ($f->getVideoUrl()) $score += 0.5;
            if ($f->getQuizs()->count() > 0) $score += 0.5;

            $scored[] = ['formation' => $f, 'score' => $score];
        }

        // Sort by score descending
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // Take top results
        $results = array_slice($scored, 0, $limit);

        return array_map(fn($item) => $item['formation'], $results);
    }
}