<?php

namespace App\Entity;

use App\Repository\PsychologueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PsychologueRepository::class)]
class Psychologue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La specialite est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'La specialite ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $specialite = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le telephone est obligatoire.')]
    #[Assert\Length(
        max: 20,
        maxMessage: 'Le telephone ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 20,
        minMessage: 'La description doit contenir au moins {{ limit }} caracteres.'
    )]
    private ?string $description = null;

    #[ORM\OneToOne(inversedBy: 'psychologue', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le compte utilisateur du psychologue est obligatoire.')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(string $specialite): self
    {
        $this->specialite = trim($specialite);

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = trim($telephone);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        if ($user !== null && $user->getPsychologue() !== $this) {
            $user->setPsychologue($this);
        }

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->user?->getName() ?? 'Psychologue';
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->getNomComplet(), $this->specialite ?? 'Specialite');
    }
}
