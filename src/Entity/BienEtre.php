<?php

namespace App\Entity;

use App\Repository\BienEtreRepository;
use Doctrine\ORM\Mapping as ORM;

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
    private ?User $user = null;

    #[ORM\Column]
    private ?int $sommeil = null;

    #[ORM\Column]
    private ?int $stress = null;

    #[ORM\Column]
    private ?int $humeur = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

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
}