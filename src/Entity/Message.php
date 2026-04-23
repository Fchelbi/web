<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_message')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_expediteur', referencedColumnName: 'id_user', nullable: false)]
    private ?User $expediteur = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_destinataire', referencedColumnName: 'id_user', nullable: false)]
    private ?User $destinataire = null;

    #[ORM\Column(type: 'text')]
    private string $contenu;

    #[ORM\Column(name: 'date_envoi', type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $dateEnvoi = null;

    #[ORM\Column(name: 'lu', type: 'boolean', nullable: true, options: ['default' => 0])]
    private bool $lu = false;

    #[ORM\Column(name: 'modifie', type: 'boolean', nullable: true, options: ['default' => 0])]
    private bool $modifie = false;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('TEXT','CALL_IN','CALL_OUT','CALL_MISSED')", nullable: true, options: ['default' => 'TEXT'])]
    private string $type = 'TEXT';

    public function __construct()
    {
        $this->dateEnvoi = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getExpediteur(): ?User { return $this->expediteur; }
    public function setExpediteur(?User $u): static { $this->expediteur = $u; return $this; }
    public function getDestinataire(): ?User { return $this->destinataire; }
    public function setDestinataire(?User $u): static { $this->destinataire = $u; return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $c): static { $this->contenu = $c; return $this; }
    public function getDateEnvoi(): ?\DateTime { return $this->dateEnvoi; }
    public function setDateEnvoi(?\DateTime $d): static { $this->dateEnvoi = $d; return $this; }
    public function isLu(): bool { return $this->lu; }
    public function setLu(bool $lu): static { $this->lu = $lu; return $this; }
    public function isModifie(): bool { return $this->modifie; }
    public function setModifie(bool $m): static { $this->modifie = $m; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
}