<?php

namespace App\Entity;

use App\Repository\ChatHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatHistoryRepository::class)]
#[ORM\Table(name: '`chathistory`')]
class ChatHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_patient = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $session_id = null;

    #[ORM\Column(type: 'string', length: 16)]
    private ?string $role = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId_patient(): ?int
    {
        return $this->id_patient;
    }

    public function setId_patient(?int $id_patient): self
    {
        $this->id_patient = $id_patient;
        return $this;
    }

    public function getSession_id(): ?string
    {
        return $this->session_id;
    }

    public function setSession_id(?string $session_id): self
    {
        $this->session_id = $session_id;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

}
