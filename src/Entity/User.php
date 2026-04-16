<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class User
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
}
