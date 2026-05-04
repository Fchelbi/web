<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // Stores the Formation object (not just the ID integer)
    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: "quizs")]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Formation $formation_id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $title = '';

    #[ORM\Column(type: "integer")]
    private int $passing_score = 60;

    // cascade persist+remove + orphanRemoval: deleting a question from the
    // collection will remove it from the DB automatically
    #[ORM\OneToMany(
        mappedBy: "quiz",
        targetEntity: Question::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: "quiz_id", targetEntity: Quiz_result::class)]
    private Collection $quiz_results;

    public function __construct()
    {
        $this->questions    = new ArrayCollection();
        $this->quiz_results = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormation_id(): ?Formation
    {
        return $this->formation_id;
    }

    public function setFormation_id(?Formation $value): self
    {
        $this->formation_id = $value;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $value): self
    {
        $this->title = $value;
        return $this;
    }

    public function getPassingScore(): int
    {
        return $this->passing_score;
    }

    public function setPassingScore(int $value): self
    {
        $this->passing_score = $value;
        return $this;
    }

    // ------------------------------------------------------------------
    // These two methods are REQUIRED by CollectionType with by_reference:false
    // Without them Symfony cannot add/remove questions from the collection
    // ------------------------------------------------------------------
    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this); // keep both sides in sync
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function getQuiz_results(): Collection
    {
        return $this->quiz_results;
    }
}