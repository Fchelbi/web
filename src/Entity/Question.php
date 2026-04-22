<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: '`question`')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quiz_id = null;

    #[ORM\Column(type: 'text')]
    private ?string $question_text = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $points = null;

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

    public function getQuestion_text(): ?string
    {
        return $this->question_text;
    }

    public function setQuestion_text(?string $question_text): self
    {
        $this->question_text = $question_text;
        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;
        return $this;
    }

}
