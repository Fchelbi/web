<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Psychologue::class)]
    private ?Psychologue $psychologue = null;

    // ===== GETTERS & SETTERS =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPsychologue(): ?Psychologue
    {
        return $this->psychologue;
    }

    public function setPsychologue(?Psychologue $psychologue): self
    {
        if ($psychologue === null && $this->psychologue !== null) {
            $this->psychologue->setUser(null);
        }

        if ($psychologue !== null && $psychologue->getUser() !== $this) {
            $psychologue->setUser($this);
        }

        $this->psychologue = $psychologue;

        return $this;
    }
}
