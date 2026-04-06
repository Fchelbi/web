<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;

#[ORM\Entity]
class Rapport
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_rapport;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "rapports")]
    #[ORM\JoinColumn(name: 'id_patient', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $id_patient;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "rapports")]
    #[ORM\JoinColumn(name: 'id_coach', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $id_coach;

    #[ORM\Column(type: "text")]
    private string $contenu;

    #[ORM\Column(type: "text")]
    private string $recommandations;

    #[ORM\Column(type: "integer")]
    private int $nb_seances;

    #[ORM\Column(type: "float")]
    private float $score_humeur;

    #[ORM\Column(type: "string", length: 255)]
    private string $periode;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_creation;

    #[ORM\Column(type: "string", length: 512)]
    private string $fichier_pdf;

    public function getId_rapport()
    {
        return $this->id_rapport;
    }

    public function setId_rapport($value)
    {
        $this->id_rapport = $value;
    }

    public function getId_patient()
    {
        return $this->id_patient;
    }

    public function setId_patient($value)
    {
        $this->id_patient = $value;
    }

    public function getId_coach()
    {
        return $this->id_coach;
    }

    public function setId_coach($value)
    {
        $this->id_coach = $value;
    }

    public function getContenu()
    {
        return $this->contenu;
    }

    public function setContenu($value)
    {
        $this->contenu = $value;
    }

    public function getRecommandations()
    {
        return $this->recommandations;
    }

    public function setRecommandations($value)
    {
        $this->recommandations = $value;
    }

    public function getNb_seances()
    {
        return $this->nb_seances;
    }

    public function setNb_seances($value)
    {
        $this->nb_seances = $value;
    }

    public function getScore_humeur()
    {
        return $this->score_humeur;
    }

    public function setScore_humeur($value)
    {
        $this->score_humeur = $value;
    }

    public function getPeriode()
    {
        return $this->periode;
    }

    public function setPeriode($value)
    {
        $this->periode = $value;
    }

    public function getDate_creation()
    {
        return $this->date_creation;
    }

    public function setDate_creation($value)
    {
        $this->date_creation = $value;
    }

    public function getFichier_pdf()
    {
        return $this->fichier_pdf;
    }

    public function setFichier_pdf($value)
    {
        $this->fichier_pdf = $value;
    }
}
