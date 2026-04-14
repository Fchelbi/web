<?php

namespace App\Entity;

use App\Repository\BienEtreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BienEtreRepository::class)]
#[ORM\Table(name: 'bien_etre')]
class BienEtre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false)]
    #[Assert\NotNull(message: 'L\'utilisateur est requis')]
    private ?User $user = null;

    // ← Sommeil: 0-100
    #[ORM\Column]
    #[Assert\NotNull(message: 'Le sommeil est requis')]
    #[Assert\Range(min: 0, max: 100,
        notInRangeMessage: 'Le sommeil doit être entre {{ min }} et {{ max }}')]
    private ?int $sommeil = null;
    #[ORM\Column]
    #[Assert\NotNull(message: 'Le stress est requis')]
    #[Assert\Range(min: 0, max: 100,
        notInRangeMessage: 'Le stress doit être entre {{ min }} et {{ max }}')]
    private ?int $stress = null;

    // ← Humeur: 0-100
    #[ORM\Column]
    #[Assert\NotNull(message: 'L\'humeur est requise')]
    #[Assert\Range(min: 0, max: 100,
        notInRangeMessage: 'L\'humeur doit être entre {{ min }} et {{ max }}')]
    private ?int $humeur = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La date est requise')]
    private ?\DateTimeImmutable $createdAt = null;


    #[ORM\Column(length: 20, nullable: true)]
    private ?string $mood = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getSommeil(): ?int { return $this->sommeil; }
    public function setSommeil(int $s): static { $this->sommeil = $s; return $this; }
    public function getStress(): ?int { return $this->stress; }
    public function setStress(int $s): static { $this->stress = $s; return $this; }
    public function getHumeur(): ?int { return $this->humeur; }
    public function setHumeur(int $h): static { $this->humeur = $h; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): static { $this->createdAt = $d; return $this; }
    public function getMood(): ?string { return $this->mood; }
    public function setMood(?string $mood): static { $this->mood = $mood; return $this; }
}