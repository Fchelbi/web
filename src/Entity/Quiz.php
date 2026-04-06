<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Formation;
use Doctrine\Common\Collections\Collection;
use App\Entity\Question;

#[ORM\Entity]
class Quiz
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: "quizs")]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Formation $formation_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "integer")]
    private int $passing_score;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getFormation_id()
    {
        return $this->formation_id;
    }

    public function setFormation_id($value)
    {
        $this->formation_id = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function getPassing_score()
    {
        return $this->passing_score;
    }

    public function setPassing_score($value)
    {
        $this->passing_score = $value;
    }

    #[ORM\OneToMany(mappedBy: "quiz_id", targetEntity: Question::class)]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: "quiz_id", targetEntity: Quiz_result::class)]
    private Collection $quiz_results;
}
