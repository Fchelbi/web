<?php

namespace App\Entity;

use App\Repository\ConsultationEnLigneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConsultationEnLigneRepository::class)]
#[ORM\Table(name: 'consultation_en_ligne')]
class ConsultationEnLigne
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRMEE = 'confirmée';
    public const STATUT_ANNULEE = 'annulée';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_CONFIRMEE,
        self::STATUT_ANNULEE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'date_consultation', type: 'datetime')]
    #[Assert\NotNull(message: 'La date de consultation est obligatoire.')]
    #[Assert\GreaterThan('now', message: 'La date de consultation doit être dans le futur.')]
    private ?\DateTimeInterface $dateConsultation = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(choices: self::STATUTS, message: 'Le statut choisi est invalide.')]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(name: 'meet_link', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le lien Meet ne doit pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Url(message: 'Veuillez saisir une URL valide pour le lien Meet.')]
    private ?string $meetLink = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Psychologue::class)]
    #[ORM\JoinColumn(name: 'psychologue_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\NotNull(message: 'Veuillez choisir un psychologue.')]
    private ?Psychologue $psychologue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateConsultation(): ?\DateTimeInterface
    {
        return $this->dateConsultation;
    }

    public function setDateConsultation(?\DateTimeInterface $dateConsultation): self
    {
        $this->dateConsultation = $dateConsultation;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getMeetLink(): ?string
    {
        return $this->meetLink;
    }

    public function setMeetLink(?string $meetLink): self
    {
        if ($meetLink !== null) {
            $meetLink = trim($meetLink);
        }

        $this->meetLink = $meetLink === '' ? null : $meetLink;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPsychologue(): ?Psychologue
    {
        return $this->psychologue;
    }

    public function setPsychologue(?Psychologue $psychologue): self
    {
        $this->psychologue = $psychologue;

        return $this;
    }

    public function getStatutLabel(): string
    {
        if ($this->statut === self::STATUT_CONFIRMEE) {
            return 'Confirmée';
        }

        if ($this->statut === self::STATUT_ANNULEE) {
            return 'Annulée';
        }

        return 'En attente';
    }

    public function getBadgeClass(): string
    {
        if ($this->statut === self::STATUT_CONFIRMEE) {
            return 'success';
        }

        if ($this->statut === self::STATUT_ANNULEE) {
            return 'danger';
        }

        return 'warning text-dark';
    }
}
