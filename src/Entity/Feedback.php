<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Formation::class)]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\Column(type: "integer")]
    private int $userId = 0;

    #[ORM\Column(type: "text")]
    private string $comment = '';

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $sentiment = null;  // positive, negative, neutral

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $confidence = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $aiSummary = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $rating = null;  // 1-5 stars

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $v): self { $this->formation = $v; return $this; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $v): self { $this->userId = $v; return $this; }

    public function getComment(): string { return $this->comment; }
    public function setComment(string $v): self { $this->comment = $v; return $this; }

    public function getSentiment(): ?string { return $this->sentiment; }
    public function setSentiment(?string $v): self { $this->sentiment = $v; return $this; }

    public function getConfidence(): ?float { return $this->confidence; }
    public function setConfidence(?float $v): self { $this->confidence = $v; return $this; }

    public function getAiSummary(): ?string { return $this->aiSummary; }
    public function setAiSummary(?string $v): self { $this->aiSummary = $v; return $this; }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $v): self { $this->rating = $v; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $v): self { $this->createdAt = $v; return $this; }
}