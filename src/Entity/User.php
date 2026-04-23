<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé !')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Min 2 caractères', maxMessage: 'Max 100 caractères')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le nom ne doit contenir que des lettres')]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est requis')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Min 2 caractères', maxMessage: 'Max 100 caractères')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le prénom ne doit contenir que des lettres')]
    private ?string $prenom = null;

    #[ORM\Column(length: 150, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'Email invalide')]
    private ?string $email = null;

    #[ORM\Column(name: 'mdp', length: 255)]
    private ?string $password = null;

    #[ORM\Column(
        type: 'string',
        columnDefinition: "ENUM('Patient','Admin','Coach')",
        nullable: true
    )]
    #[Assert\Choice(choices: ['Patient', 'Admin', 'Coach'], message: 'Rôle invalide')]
    private ?string $role = null;

    #[ORM\Column(name: 'num_tel', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^\d{8}$/', message: 'Le téléphone doit contenir 8 chiffres')]
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $faceDescriptor = null;

    #[Vich\UploadableField(mapping: 'user_photo', fileNameProperty: 'photo')]
    private ?File $photoFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $banned = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $badWordsCount = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $bannedAt = null;

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
    public function getFaceDescriptor(): ?string { return $this->faceDescriptor; }
    public function setFaceDescriptor(?string $f): static { $this->faceDescriptor = $f; return $this; }


    public function getRoles(): array
    {
        return ['ROLE_' . strtoupper($this->role ?? 'PATIENT')];
    }

    public function getUserIdentifier(): string 
    { 
        return (string) $this->email; 
    }
    public function eraseCredentials(): void {}

    public function __toString(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
    public function getPhotoFile(): ?File { return $this->photoFile; }
    public function setPhotoFile(?File $file): static {
        $this->photoFile = $file;
        if ($file) $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $t): static { $this->updatedAt = $t; return $this; }
    public function isBanned(): bool { return $this->banned; }
    public function setBanned(bool $banned): static { $this->banned = $banned; return $this; }
    public function getBadWordsCount(): int { return $this->badWordsCount; }
    public function setBadWordsCount(int $count): static { $this->badWordsCount = $count; return $this; }
    public function incrementBadWordsCount(): static { $this->badWordsCount++; return $this; }
    public function getBannedAt(): ?\DateTime { return $this->bannedAt; }
    public function setBannedAt(?\DateTime $t): static { $this->bannedAt = $t; return $this; }
}