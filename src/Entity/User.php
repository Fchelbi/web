<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_PATIENT = 'Patient';
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_COACH = 'Coach';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom', type: 'string', length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'email', type: 'string', length: 150, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'mdp', type: 'string', length: 255)]
    private ?string $mdp = null;

    #[ORM\Column(name: 'role', type: 'string', length: 20)]
    private ?string $role = null;

    #[ORM\Column(name: 'num_tel', type: 'string', length: 20, nullable: true)]
    private ?string $numTel = null;

    #[ORM\Column(name: 'photo', type: 'string', length: 500, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(name: 'device_token', type: 'string', length: 500, nullable: true)]
    private ?string $deviceToken = null;

    #[ORM\Column(name: 'two_factor_code', type: 'string', length: 10, nullable: true)]
    private ?string $twoFactorCode = null;

    #[ORM\Column(name: 'two_factor_expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $twoFactorExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom !== null ? trim($nom) : null;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom !== null ? trim($prenom) : null;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email !== null ? trim($email) : null;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(?string $mdp): self
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getNumTel(): ?string
    {
        return $this->numTel;
    }

    public function setNumTel(?string $numTel): self
    {
        $this->numTel = $numTel !== null ? trim($numTel) : null;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getDeviceToken(): ?string
    {
        return $this->deviceToken;
    }

    public function setDeviceToken(?string $deviceToken): self
    {
        $this->deviceToken = $deviceToken;

        return $this;
    }

    public function getTwoFactorCode(): ?string
    {
        return $this->twoFactorCode;
    }

    public function setTwoFactorCode(?string $twoFactorCode): self
    {
        $this->twoFactorCode = $twoFactorCode;

        return $this;
    }

    public function getTwoFactorExpiresAt(): ?\DateTimeImmutable
    {
        return $this->twoFactorExpiresAt;
    }

    public function setTwoFactorExpiresAt(?\DateTimeImmutable $twoFactorExpiresAt): self
    {
        $this->twoFactorExpiresAt = $twoFactorExpiresAt;

        return $this;
    }

    public function getName(): string
    {
        return trim(sprintf('%s %s', $this->prenom ?? '', $this->nom ?? '')) ?: 'Utilisateur';
    }

    public function setName(string $name): self
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        $this->prenom = $parts[0] ?? $name;
        $this->nom = $parts[1] ?? $name;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->getName();
    }

    public function __toString(): string
    {
        return $this->getNomComplet();
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER']; // Always include base role
        if ($this->role) {
            $roles[] = 'ROLE_' . strtoupper($this->role);
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->mdp;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
