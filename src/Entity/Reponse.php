<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
#[ORM\Table(name: '`reponse`')]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $question_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $option_text = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_correct = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getQuestion_id(): ?int
    {
        return $this->question_id;
    }

    public function setQuestion_id(?int $question_id): self
    {
        $this->question_id = $question_id;
        return $this;
    }

    public function getOption_text(): ?string
    {
        return $this->option_text;
    }

    public function setOption_text(?string $option_text): self
    {
        $this->option_text = $option_text;
        return $this;
    }

    public function getIs_correct(): ?bool
    {
        return $this->is_correct;
    }

    public function setIs_correct(?bool $is_correct): self
    {
        $this->is_correct = $is_correct;
        return $this;
    }

}
