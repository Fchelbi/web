<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Participation;

#[ORM\Entity]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $video_url = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $coach_id = null;

    #[ORM\OneToMany(mappedBy: "formation_id", targetEntity: Quiz::class)]
    private Collection $quizs;

    #[ORM\OneToMany(mappedBy: "formation_id", targetEntity: Participation::class)]
    private Collection $participations;

    public function __construct()
    {
        $this->quizs = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $value): self
    {
        $this->title = $value;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $value): self
    {
        $this->description = $value;
        return $this;
    }

    // camelCase getter for Twig
    public function getVideoUrl(): ?string
    {
        return $this->video_url;
    }

    public function setVideoUrl(?string $value): self
    {
        $this->video_url = $value;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $value): self
    {
        $this->category = $value;
        return $this;
    }

    // camelCase getter for Twig
    public function getCoachId(): ?int
    {
        return $this->coach_id;
    }

    public function setCoachId(?int $value): self
    {
        $this->coach_id = $value;
        return $this;
    }

    public function getQuizs(): Collection
    {
        return $this->quizs;
    }

    public function addQuiz(Quiz $quiz): self
    {
        if (!$this->quizs->contains($quiz)) {
            $this->quizs[] = $quiz;
            $quiz->setFormationId($this);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        if ($this->quizs->removeElement($quiz)) {
            if ($quiz->getFormationId() === $this) {
                $quiz->setFormationId(null);
            }
        }
        return $this;
    }

    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setFormationId($this);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getFormationId() === $this) {
                $participation->setFormationId(null);
            }
        }
        return $this;
    }
}