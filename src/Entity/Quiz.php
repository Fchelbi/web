<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\Table(name: '`quiz`')]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $formation_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $passing_score = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFormation_id(): ?int
    {
        return $this->formation_id;
    }

    public function setFormation_id(?int $formation_id): self
    {
        $this->formation_id = $formation_id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getPassing_score(): ?int
    {
        return $this->passing_score;
    }

    public function setPassing_score(?int $passing_score): self
    {
        $this->passing_score = $passing_score;
        return $this;
    }

}
