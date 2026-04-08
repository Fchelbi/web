<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'formation')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // ---- Contrôles de saisie: titre ----
    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 3, max: 255,
        minMessage: "Le titre doit avoir au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    // Test logique: no special characters that could break HTML/DB
    #[Assert\Regex(
        pattern: '/^[\p{L}0-9\s\'\-\,\.\!\?\:\(\)\/]+$/u',
        message: "Le titre contient des caractères non autorisés."
    )]
    private string $title = '';

    // ---- Contrôles de saisie: description ----
    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    // ---- Contrôles de saisie: URL vidéo ----
    #[ORM\Column(type: "string", length: 500, nullable: true, name: "video_url")]
    #[Assert\Url(
        message: "L'URL de la vidéo n'est pas valide. Elle doit commencer par https://",
        requireTld: true
    )]
    private ?string $videoUrl = null;

    // ---- Contrôles de saisie + test logique: catégorie ----
    #[ORM\Column(type: "string", length: 100, nullable: true)]
    #[Assert\NotBlank(message: "La catégorie est obligatoire.")]
    // Test logique: value must be one of the allowed choices
    #[Assert\Choice(
        choices: ['Nutrition','Sport & Fitness','Santé Mentale','Méditation','Gestion du Stress','Autre'],
        message: "Catégorie invalide. Choisissez une valeur dans la liste."
    )]
    private ?string $category = null;

    #[ORM\Column(type: "integer", nullable: true, name: "coach_id")]
    private ?int $coachId = null;

    // FIX: cascade persist+remove added — Doctrine now automatically
    // persists/removes quizs when the formation is managed
    #[ORM\OneToMany(
        mappedBy: "formation_id",
        targetEntity: Quiz::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $quizs;

    #[ORM\OneToMany(mappedBy: "formation_id", targetEntity: Participation::class)]
    private Collection $participations;

    public function __construct()
    {
        $this->quizs          = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $v): self { $this->title = $v; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }

    public function getVideoUrl(): ?string { return $this->videoUrl; }
    public function setVideoUrl(?string $v): self { $this->videoUrl = $v; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $v): self { $this->category = $v; return $this; }

    public function getCoachId(): ?int { return $this->coachId; }
    public function setCoachId(?int $v): self { $this->coachId = $v; return $this; }

    public function getQuizs(): Collection { return $this->quizs; }

    // Both-sides sync — required when creating a quiz
    public function addQuiz(Quiz $quiz): self
    {
        if (!$this->quizs->contains($quiz)) {
            $this->quizs->add($quiz);
            $quiz->setFormation_id($this);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        if ($this->quizs->removeElement($quiz)) {
            if ($quiz->getFormation_id() === $this) {
                $quiz->setFormation_id(null);
            }
        }
        return $this;
    }

    public function getParticipations(): Collection { return $this->participations; }
}