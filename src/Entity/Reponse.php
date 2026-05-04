<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // Property name is $question (holds the full object, not just an ID)
    // mappedBy in Question must match: mappedBy: 'question'
    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: "reponses")]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Question $question = null;

    // camelCase — ReponseType field name must match: 'optionText'
    #[ORM\Column(type: "string", length: 255)]
    private string $optionText = '';

    // camelCase — ReponseType field name must match: 'isCorrect'
    #[ORM\Column(type: "boolean")]
    private bool $isCorrect = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOptionText(): string
    {
        return $this->optionText;
    }

    public function setOptionText(string $optionText): self
    {
        $this->optionText = $optionText;
        return $this;
    }

    public function getIsCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }
}