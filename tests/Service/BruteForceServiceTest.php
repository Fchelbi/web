<?php

namespace App\Tests\Service;

use App\Entity\LoginAttempt;
use App\Repository\LoginAttemptRepository;
use App\Service\BruteForceService;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BruteForceServiceTest extends TestCase
{
    private BruteForceService $service;
    private LoginAttemptRepository $repo;
    private EntityManagerInterface $em;
    private MailService $mail;

    protected function setUp(): void
    {
        $this->repo  = $this->createMock(LoginAttemptRepository::class);
        $this->em    = $this->createMock(EntityManagerInterface::class);
        $this->mail  = $this->createMock(MailService::class);
        $this->service = new BruteForceService($this->repo, $this->em, $this->mail);
    }

    public function testIsNotBlockedWhenNoAttempt(): void
    {
        $this->repo->method('findByEmail')->willReturn(null);
        $this->assertFalse($this->service->isBlocked('test@test.com'));
    }

    public function testIsNotBlockedWhenNoBlockedUntil(): void
    {
        $attempt = new LoginAttempt();
        $attempt->setEmail('test@test.com');
        $attempt->setIp('127.0.0.1');
        $attempt->setAttempts(3);

        $this->repo->method('findByEmail')->willReturn($attempt);
        $this->assertFalse($this->service->isBlocked('test@test.com'));
    }

    public function testIsBlockedWhenBlockedUntilInFuture(): void
    {
        $attempt = new LoginAttempt();
        $attempt->setEmail('test@test.com');
        $attempt->setIp('127.0.0.1');
        $attempt->setAttempts(5);
        $attempt->setBlockedUntil(new \DateTime('+10 minutes'));

        $this->repo->method('findByEmail')->willReturn($attempt);
        $this->assertTrue($this->service->isBlocked('test@test.com'));
    }

    public function testIsNotBlockedWhenBlockedUntilInPast(): void
    {
        $attempt = new LoginAttempt();
        $attempt->setEmail('test@test.com');
        $attempt->setIp('127.0.0.1');
        $attempt->setAttempts(5);
        $attempt->setBlockedUntil(new \DateTime('-10 minutes'));

        $this->repo->method('findByEmail')->willReturn($attempt);
        $this->assertFalse($this->service->isBlocked('test@test.com'));
    }

    public function testGetRemainingTimeWhenNotBlocked(): void
    {
        $this->repo->method('findByEmail')->willReturn(null);
        $this->assertEquals(0, $this->service->getRemainingTime('test@test.com'));
    }

    public function testGetAttemptsWhenNoAttempt(): void
    {
        $this->repo->method('findByEmail')->willReturn(null);
        $this->assertEquals(0, $this->service->getAttempts('test@test.com'));
    }

    public function testGetAttemptsWhenAttemptExists(): void
    {
        $attempt = new LoginAttempt();
        $attempt->setEmail('test@test.com');
        $attempt->setIp('127.0.0.1');
        $attempt->setAttempts(3);

        $this->repo->method('findByEmail')->willReturn($attempt);
        $this->assertEquals(3, $this->service->getAttempts('test@test.com'));
    }
}