<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Quiz_result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]   // was missing — without this INSERT fails
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: "quiz_results")]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Quiz $quiz_id = null;

    // Stores the User ID as an integer (no FK relation to User entity here)
    #[ORM\Column(type: "integer")]
    private int $user_id = 0;

    #[ORM\Column(type: "integer")]
    private int $score = 0;

    #[ORM\Column(type: "integer")]
    private int $total_points = 0;

    #[ORM\Column(type: "boolean")]
    private bool $passed = false;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $completed_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz_id(): ?Quiz
    {
        return $this->quiz_id;
    }

    public function setQuiz_id(?Quiz $value): self
    {
        $this->quiz_id = $value;
        return $this;
    }

    public function getUser_id(): int
    {
        return $this->user_id;
    }

    public function setUser_id(int $value): self
    {
        $this->user_id = $value;
        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $value): self
    {
        $this->score = $value;
        return $this;
    }

    public function getTotal_points(): int
    {
        return $this->total_points;
    }

    public function setTotal_points(int $value): self
    {
        $this->total_points = $value;
        return $this;
    }

    public function getPassed(): bool
    {
        return $this->passed;
    }

    public function setPassed(bool $value): self
    {
        $this->passed = $value;
        return $this;
    }

    public function getCompleted_at(): \DateTimeInterface
    {
        return $this->completed_at;
    }

    public function setCompleted_at(\DateTimeInterface $value): self
    {
        $this->completed_at = $value;
        return $this;
    }
}