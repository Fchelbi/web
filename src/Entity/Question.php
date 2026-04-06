<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Quiz;
use Doctrine\Common\Collections\Collection;
use App\Entity\Reponse;

#[ORM\Entity]
class Question
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: "questions")]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Quiz $quiz_id;

    #[ORM\Column(type: "text")]
    private string $question_text;

    #[ORM\Column(type: "integer")]
    private int $points;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getQuiz_id()
    {
        return $this->quiz_id;
    }

    public function setQuiz_id($value)
    {
        $this->quiz_id = $value;
    }

    public function getQuestion_text()
    {
        return $this->question_text;
    }

    public function setQuestion_text($value)
    {
        $this->question_text = $value;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function setPoints($value)
    {
        $this->points = $value;
    }

    #[ORM\OneToMany(mappedBy: "question_id", targetEntity: Reponse::class)]
    private Collection $reponses;

        public function getReponses(): Collection
        {
            return $this->reponses;
        }
    
        public function addReponse(Reponse $reponse): self
        {
            if (!$this->reponses->contains($reponse)) {
                $this->reponses[] = $reponse;
                $reponse->setQuestion_id($this);
            }
    
            return $this;
        }
    
        public function removeReponse(Reponse $reponse): self
        {
            if ($this->reponses->removeElement($reponse)) {
                // set the owning side to null (unless already changed)
                if ($reponse->getQuestion_id() === $this) {
                    $reponse->setQuestion_id(null);
                }
            }
    
            return $this;
        }
}
