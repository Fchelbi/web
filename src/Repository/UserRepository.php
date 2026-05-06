<?php

namespace App\Repository;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Get all coaches with their consultation counts.
     * Optimized with GROUP BY to avoid N+1 queries.
     *
     * @param \DateTimeInterface|null $fromDate Optional: count only consultations from this date
     * @return array Array of coaches with their consultation counts:
     *               [
     *                   {"id": 1, "name": "John Doe", "consultations": 5},
     *                   {"id": 2, "name": "Sarah Smith", "consultations": 2}
     *               ]
     */
    public function findCoachesWithConsultationCounts(?\DateTimeInterface $fromDate = null): array
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.id, CONCAT(u.prenom, \' \', u.nom) as name, COUNT(c.id) as consultations')
            ->leftJoin(ConsultationEnLigne::class, 'c', 'WITH', 'c.psychologue = u.id')
            ->where('u.role = :role')
            ->setParameter('role', User::ROLE_COACH)
            ->groupBy('u.id')
            ->orderBy('consultations', 'ASC');

        // Optional: filter by date (count only upcoming consultations from this date)
        if ($fromDate !== null) {
            $qb->andWhere('c.dateConsultation IS NULL OR c.dateConsultation >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(
            fn($row) => [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'consultations' => (int) $row['consultations'],
            ],
            $results
        );
    }

    /**
     * Get all coaches (Users with role = Coach).
     *
     * @return User[]
     */
    public function findAllCoaches(): array
    {
        return $this->findBy(['role' => User::ROLE_COACH]);
    }

    public function createCoachesQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', User::ROLE_COACH)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC');
    }

    /**
     * Get a single coach by ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findCoachById(int $id): ?User
    {
        return $this->findOneBy(['id' => $id, 'role' => User::ROLE_COACH]);
    }
}
