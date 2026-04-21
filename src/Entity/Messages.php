<?php

namespace App\Entity;

use App\Repository\MessagesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessagesRepository::class)]
#[ORM\Table(name: '`messages`')]
class Messages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_message = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_expediteur = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_destinataire = null;

    #[ORM\Column(type: 'text')]
    private ?string $contenu = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_envoi = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $lu = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $modifie = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $type = null;

    public function getId_message(): ?int
    {
        return $this->id_message;
    }

    public function setId_message(?int $id_message): self
    {
        $this->id_message = $id_message;
        return $this;
    }

    public function getId_expediteur(): ?int
    {
        return $this->id_expediteur;
    }

    public function setId_expediteur(?int $id_expediteur): self
    {
        $this->id_expediteur = $id_expediteur;
        return $this;
    }

    public function getId_destinataire(): ?int
    {
        return $this->id_destinataire;
    }

    public function setId_destinataire(?int $id_destinataire): self
    {
        $this->id_destinataire = $id_destinataire;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDate_envoi(): ?\DateTimeInterface
    {
        return $this->date_envoi;
    }

    public function setDate_envoi(?\DateTimeInterface $date_envoi): self
    {
        $this->date_envoi = $date_envoi;
        return $this;
    }

    public function getLu(): ?bool
    {
        return $this->lu;
    }

    public function setLu(?bool $lu): self
    {
        $this->lu = $lu;
        return $this;
    }

    public function getModifie(): ?bool
    {
        return $this->modifie;
    }

    public function setModifie(?bool $modifie): self
    {
        $this->modifie = $modifie;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

}
