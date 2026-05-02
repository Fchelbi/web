<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function searchByKeyword(string $query, int $limit = 5, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.title LIKE :query OR p.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countFlagged(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isFlagged = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFlaggedPosts(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.isFlagged = true')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPostsPerDay(int $days = 30): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DATE(created_at) as day, COUNT(*) as total
                FROM post
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY day
                ORDER BY day ASC";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['days' => $days]);
        $rows = $result->fetchAllAssociative();

        $data = [];
        foreach ($rows as $row) {
            $data[$row['day']] = (int) $row['total'];
        }
        return $data;
    }

    public function getPostsByCategory(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT c.name as category_name, COUNT(p.id) as total
                FROM post p
                JOIN category c ON p.category_id = c.id
                GROUP BY c.id, c.name
                ORDER BY total DESC";
        $result = $conn->executeQuery($sql);
        $rows = $result->fetchAllAssociative();

        $data = [];
        foreach ($rows as $row) {
            $data[$row['category_name']] = (int) $row['total'];
        }
        return $data;
    }

    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
