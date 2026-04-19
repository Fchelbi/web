<?php

namespace App\Entity;

use App\Repository\LoginAttemptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginAttemptRepository::class)]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    private string $ip;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    #[ORM\Column]
    private int $attempts = 1;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $lastAttempt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $blockedUntil = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->lastAttempt = new \DateTime();
        $this->createdAt   = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getIp(): string { return $this->ip; }
    public function setIp(string $ip): static { $this->ip = $ip; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function getAttempts(): int { return $this->attempts; }
    public function setAttempts(int $attempts): static { $this->attempts = $attempts; return $this; }
    public function incrementAttempts(): static { $this->attempts++; return $this; }
    public function getLastAttempt(): \DateTime { return $this->lastAttempt; }
    public function setLastAttempt(\DateTime $lastAttempt): static { $this->lastAttempt = $lastAttempt; return $this; }
    public function getBlockedUntil(): ?\DateTime { return $this->blockedUntil; }
    public function setBlockedUntil(?\DateTime $blockedUntil): static { $this->blockedUntil = $blockedUntil; return $this; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    public function isBlocked(): bool
    {
        if (!$this->blockedUntil) return false;
        return $this->blockedUntil > new \DateTime();
    }
}