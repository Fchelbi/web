<?php

namespace App\Repository;

use App\Entity\ConsultationEnLigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConsultationEnLigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsultationEnLigne::class);
    }

    public function findByStatut(?string $statut): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->orderBy('c.dateConsultation', 'ASC');

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

        return $qb->getQuery()->getResult();
    }

    public function isDateAlreadyUsed(\DateTimeInterface $dateConsultation, ?int $excludedId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.dateConsultation = :dateConsultation')
            ->setParameter('dateConsultation', $dateConsultation);

        if ($excludedId !== null) {
            $qb->andWhere('c.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getStatutCounts(): array
    {
        $counts = [
            ConsultationEnLigne::STATUT_EN_ATTENTE => 0,
            ConsultationEnLigne::STATUT_CONFIRMEE => 0,
            ConsultationEnLigne::STATUT_ANNULEE => 0,
        ];

        $rows = $this->createQueryBuilder('c')
            ->select('c.statut AS statut, COUNT(c.id) AS total')
            ->groupBy('c.statut')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $counts[$row['statut']] = (int) $row['total'];
        }

        return $counts;
    }
}
