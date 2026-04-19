<?php

namespace App\Service;

use App\Entity\LoginAttempt;
use App\Repository\LoginAttemptRepository;
use Doctrine\ORM\EntityManagerInterface;

class BruteForceService
{
    private const MAX_ATTEMPTS = 5;
    private const BLOCK_MINUTES = 5;

    public function __construct(
        private LoginAttemptRepository $repo,
        private EntityManagerInterface $em,
        private MailService $mailService
    ) {}

    public function isBlocked(string $email): bool
    {
        $attempt = $this->repo->findByEmail($email);
        if (!$attempt) return false;
        if (!$attempt->getBlockedUntil()) return false;
        return $attempt->getBlockedUntil() > new \DateTime();
    }

    public function getRemainingTime(string $email): int
    {
        $attempt = $this->repo->findByEmail($email);
        if (!$attempt || !$attempt->getBlockedUntil()) return 0;
        $diff = $attempt->getBlockedUntil()->getTimestamp() - time();
        return max(0, $diff); // Retourne les secondes exactes !
    }

    public function getAttempts(string $email): int
    {
        $attempt = $this->repo->findByEmail($email);
        return $attempt ? $attempt->getAttempts() : 0;
    }

    public function recordFailedAttempt(string $ip, string $email): void
    {
        if (!$email) return;

        $attempt = $this->repo->findByEmail($email);

        if (!$attempt) {
            $attempt = new LoginAttempt();
            $attempt->setIp($ip);
            $attempt->setEmail($email);
            $this->em->persist($attempt);
        } else {
            $attempt->incrementAttempts();
            $attempt->setLastAttempt(new \DateTime());
            $attempt->setIp($ip);
        }

        if ($attempt->getAttempts() >= self::MAX_ATTEMPTS) {
            $blockedUntil = new \DateTime('+' . self::BLOCK_MINUTES . ' minutes');
            $attempt->setBlockedUntil($blockedUntil);
            $this->sendAlertToAdmin($ip, $email, $attempt->getAttempts());
        }

        $this->em->flush();
    }

    public function recordSuccessfulLogin(string $email): void
    {
        $attempt = $this->repo->findByEmail($email);
        if ($attempt && !$attempt->isBlocked()) {
            $this->em->remove($attempt);
            $this->em->flush();
        }
    }

    private function sendAlertToAdmin(string $ip, string $email, int $attempts): void
    {
        try {
            $this->mailService->sendSecurityAlert($ip, $email, $attempts);
        } catch (\Exception $e) {}
    }
}