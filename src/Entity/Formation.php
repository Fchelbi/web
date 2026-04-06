<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Participation;

#[ORM\Entity]
class Formation
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 255)]
    private string $video_url;

    #[ORM\Column(type: "string", length: 100)]
    private string $category;

    #[ORM\Column(type: "integer")]
    private int $coach_id;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getVideo_url()
    {
        return $this->video_url;
    }

    public function setVideo_url($value)
    {
        $this->video_url = $value;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($value)
    {
        $this->category = $value;
    }

    public function getCoach_id()
    {
        return $this->coach_id;
    }

    public function setCoach_id($value)
    {
        $this->coach_id = $value;
    }

    #[ORM\OneToMany(mappedBy: "formation_id", targetEntity: Quiz::class)]
    private Collection $quizs;

        public function getQuizs(): Collection
        {
            return $this->quizs;
        }
    
        public function addQuiz(Quiz $quiz): self
        {
            if (!$this->quizs->contains($quiz)) {
                $this->quizs[] = $quiz;
                $quiz->setFormation_id($this);
            }
    
            return $this;
        }
    
        public function removeQuiz(Quiz $quiz): self
        {
            if ($this->quizs->removeElement($quiz)) {
                // set the owning side to null (unless already changed)
                if ($quiz->getFormation_id() === $this) {
                    $quiz->setFormation_id(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "formation_id", targetEntity: Participation::class)]
    private Collection $participations;
}
