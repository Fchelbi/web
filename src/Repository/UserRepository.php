<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    public function searchPaginated(
        string $search,
        string $role,
        int $page,
        int $limit
    ): array {
        $qb = $this->createQueryBuilder('u');

        // ← Filtre recherche texte (nom, prénom, email)
        if ($search !== '') {
            $qb->andWhere(
                'u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s'
            )->setParameter('s', '%' . $search . '%');
        }
        if ($role !== '') {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
        }

        // ← Total (pour calculer nb pages)
        $total = (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // ← Résultats paginés
        $users = $qb
            ->orderBy('u.nom', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [$users, (int) $total];
    }
}