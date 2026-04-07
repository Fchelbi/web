<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'mdp', length: 255)]
    private ?string $password = null;

    #[ORM\Column(
        type: 'string',
        columnDefinition: "ENUM('Patient','Admin','Coach')",
        nullable: true
    )]
    private ?string $role = null;

    #[ORM\Column(name: 'num_tel', length: 20, nullable: true)]
    private ?string $numTel = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $twoFactorCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $twoFactorExpiresAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $deviceToken = null;

    // ===== GETTERS / SETTERS =====

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): static { $this->role = $role; return $this; }

    public function getNumTel(): ?string { return $this->numTel; }
    public function setNumTel(?string $numTel): static { $this->numTel = $numTel; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): static { $this->photo = $photo; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $t): static { $this->resetToken = $t; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeImmutable $t): static { $this->resetTokenExpiresAt = $t; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $v): static { $this->isVerified = $v; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $t): static { $this->verificationToken = $t; return $this; }

    public function getTwoFactorCode(): ?string { return $this->twoFactorCode; }
    public function setTwoFactorCode(?string $code): static { $this->twoFactorCode = $code; return $this; }

    public function getTwoFactorExpiresAt(): ?\DateTimeImmutable { return $this->twoFactorExpiresAt; }
    public function setTwoFactorExpiresAt(?\DateTimeImmutable $t): static { $this->twoFactorExpiresAt = $t; return $this; }

    public function getDeviceToken(): ?string { return $this->deviceToken; }
    public function setDeviceToken(?string $t): static { $this->deviceToken = $t; return $this; }

    // ===== SYMFONY SECURITY =====

    public function getRoles(): array
    {
        return ['ROLE_' . strtoupper($this->role ?? 'PATIENT')];
    }

    public function getUserIdentifier(): string { return (string) $this->email; }
    public function eraseCredentials(): void {}
}