<?php

namespace App\Repository;

use App\Entity\LoginAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LoginAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    public function findByIp(string $ip): ?LoginAttempt
    {
        return $this->createQueryBuilder('l')
            ->where('l.ip = :ip')
            ->setParameter('ip', $ip)
            ->orderBy('l.lastAttempt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countRecentAttempts(string $ip, int $minutes = 15): int
    {
        $since = new \DateTime("-{$minutes} minutes");
        return (int) $this->createQueryBuilder('l')
            ->select('SUM(l.attempts)')
            ->where('l.ip = :ip')
            ->andWhere('l.lastAttempt >= :since')
            ->setParameter('ip', $ip)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getRecentBlocked(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.blockedUntil > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('l.lastAttempt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function getAllSuspicious(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.attempts >= 3')
            ->orderBy('l.lastAttempt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
    public function findByEmail(string $email): ?LoginAttempt
    {
        return $this->createQueryBuilder('l')
            ->where('l.email = :email')
            ->setParameter('email', $email)
            ->orderBy('l.lastAttempt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}