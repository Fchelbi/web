<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Quiz;

#[ORM\Entity]
class Quiz_result
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: "quiz_results")]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Quiz $quiz_id;

    #[ORM\Column(type: "integer")]
    private int $user_id;

    #[ORM\Column(type: "integer")]
    private int $score;

    #[ORM\Column(type: "integer")]
    private int $total_points;

    #[ORM\Column(type: "boolean")]
    private bool $passed;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $completed_at;

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

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setScore($value)
    {
        $this->score = $value;
    }

    public function getTotal_points()
    {
        return $this->total_points;
    }

    public function setTotal_points($value)
    {
        $this->total_points = $value;
    }

    public function getPassed()
    {
        return $this->passed;
    }

    public function setPassed($value)
    {
        $this->passed = $value;
    }

    public function getCompleted_at()
    {
        return $this->completed_at;
    }

    public function setCompleted_at($value)
    {
        $this->completed_at = $value;
    }
}
