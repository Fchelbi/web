<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Participation;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'formation')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Le titre doit avoir au moins 3 caractères")]
    private string $title = '';

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: "La description ne peut pas dépasser 2000 caractères")]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255, nullable: true, name: "video_url")]
    #[Assert\Url(message: "L'URL de la vidéo n'est pas valide")]
    private ?string $videoUrl = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    #[Assert\NotBlank(message: "La catégorie est obligatoire")]
    private ?string $category = null;

    #[ORM\Column(type: "integer", nullable: true, name: "coach_id")]
    private ?int $coachId = null;

    #[ORM\OneToMany(mappedBy: "formation_id", targetEntity: Quiz::class)]
    private Collection $quizs;

    #[ORM\OneToMany(mappedBy: "formation_id", targetEntity: Participation::class)]
    private Collection $participations;

    public function __construct()
    {
        $this->quizs = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $value): self { $this->title = $value; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $value): self { $this->description = $value; return $this; }

    public function getVideoUrl(): ?string { return $this->videoUrl; }
    public function setVideoUrl(?string $value): self { $this->videoUrl = $value; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $value): self { $this->category = $value; return $this; }

    public function getCoachId(): ?int { return $this->coachId; }
    public function setCoachId(?int $value): self { $this->coachId = $value; return $this; }

    public function getQuizs(): Collection { return $this->quizs; }
    public function getParticipations(): Collection { return $this->participations; }
}
