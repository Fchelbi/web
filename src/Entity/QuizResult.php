<?php

namespace App\Entity;

use App\Repository\QuizResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizResultRepository::class)]
#[ORM\Table(name: '`quizresult`')]
class QuizResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quiz_id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $user_id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $score = null;

    #[ORM\Column(type: 'integer')]
    private ?int $total_points = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $passed = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completed_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getQuiz_id(): ?int
    {
        return $this->quiz_id;
    }

    public function setQuiz_id(?int $quiz_id): self
    {
        $this->quiz_id = $quiz_id;
        return $this;
    }

    public function getUser_id(): ?int
    {
        return $this->user_id;
    }

    public function setUser_id(?int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getTotal_points(): ?int
    {
        return $this->total_points;
    }

    public function setTotal_points(?int $total_points): self
    {
        $this->total_points = $total_points;
        return $this;
    }

    public function getPassed(): ?bool
    {
        return $this->passed;
    }

    public function setPassed(?bool $passed): self
    {
        $this->passed = $passed;
        return $this;
    }

    public function getCompleted_at(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function setCompleted_at(?\DateTimeInterface $completed_at): self
    {
        $this->completed_at = $completed_at;
        return $this;
    }

}
