<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Quiz $quiz = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $questionText = null;

    #[ORM\Column]
    private int $points = 1;

    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\OneToMany(
        mappedBy: 'question',
        targetEntity: Reponse::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getQuiz(): ?Quiz { return $this->quiz; }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getQuestionText(): ?string { return $this->questionText; }

    public function setQuestionText(string $questionText): self
    {
        $this->questionText = $questionText;
        return $this;
    }

    public function getPoints(): int { return $this->points; }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setQuestion($this);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getQuestion() === $this) {
                $reponse->setQuestion(null);
            }
        }
        return $this;
    }
}