<?php

namespace App\Entity;

use App\Repository\RapportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RapportRepository::class)]
#[ORM\Table(name: '`rapport`')]
class Rapport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_rapport = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_patient = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_coach = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $recommandations = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nb_seances = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score_humeur = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $periode = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $fichier_pdf = null;

    public function getId_rapport(): ?int
    {
        return $this->id_rapport;
    }

    public function setId_rapport(?int $id_rapport): self
    {
        $this->id_rapport = $id_rapport;
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

    public function getId_coach(): ?int
    {
        return $this->id_coach;
    }

    public function setId_coach(?int $id_coach): self
    {
        $this->id_coach = $id_coach;
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

    public function getRecommandations(): ?string
    {
        return $this->recommandations;
    }

    public function setRecommandations(?string $recommandations): self
    {
        $this->recommandations = $recommandations;
        return $this;
    }

    public function getNb_seances(): ?int
    {
        return $this->nb_seances;
    }

    public function setNb_seances(?int $nb_seances): self
    {
        $this->nb_seances = $nb_seances;
        return $this;
    }

    public function getScore_humeur(): ?float
    {
        return $this->score_humeur;
    }

    public function setScore_humeur(?float $score_humeur): self
    {
        $this->score_humeur = $score_humeur;
        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(?string $periode): self
    {
        $this->periode = $periode;
        return $this;
    }

    public function getDate_creation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDate_creation(?\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getFichier_pdf(): ?string
    {
        return $this->fichier_pdf;
    }

    public function setFichier_pdf(?string $fichier_pdf): self
    {
        $this->fichier_pdf = $fichier_pdf;
        return $this;
    }

}
