<?php

namespace App\Entity;

use App\Repository\ConsultationEnLigneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
    #[Assert\GreaterThan('now', message: 'La date de consultation doit etre dans le futur.')]
    private ?\DateTimeInterface $dateConsultation = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le motif de consultation est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le motif doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le motif ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $motif = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(choices: self::STATUTS, message: 'Le statut choisi est invalide.')]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(name: 'meet_link', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le lien Meet ne doit pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Url(message: 'Veuillez saisir une URL valide pour le lien Meet.')]
    private ?string $meetLink = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'psychologue_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Veuillez choisir un psychologue.')]
    private ?User $psychologue = null;

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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif !== null ? trim($motif) : null;

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

    public function getPsychologue(): ?User
    {
        return $this->psychologue;
    }

    public function setPsychologue(?User $psychologue): self
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

    #[Assert\Callback]
    public function validateDateConsultation(ExecutionContextInterface $context): void
    {
        if (!$this->dateConsultation instanceof \DateTimeInterface) {
            return;
        }

        $minimumDate = new \DateTimeImmutable('+1 hour');

        if ($this->dateConsultation < $minimumDate) {
            $context->buildViolation('La consultation doit etre reservee au moins 1 heure a l avance.')
                ->atPath('dateConsultation')
                ->addViolation();
        }

        $time = $this->dateConsultation->format('H:i');

        if ($time < '08:00' || $time > '20:00') {
            $context->buildViolation('Les consultations sont disponibles uniquement entre 08:00 et 20:00.')
                ->atPath('dateConsultation')
                ->addViolation();
        }
    }
}
