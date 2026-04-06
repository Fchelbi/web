<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;

#[ORM\Entity]
class Messages
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_message;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "messagess")]
    #[ORM\JoinColumn(name: 'id_expediteur', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $id_expediteur;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "messagess")]
    #[ORM\JoinColumn(name: 'id_destinataire', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $id_destinataire;

    #[ORM\Column(type: "text")]
    private string $contenu;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_envoi;

    #[ORM\Column(type: "boolean")]
    private bool $lu;

    #[ORM\Column(type: "boolean")]
    private bool $modifie;

    #[ORM\Column(type: "string")]
    private string $type;

    public function getId_message()
    {
        return $this->id_message;
    }

    public function setId_message($value)
    {
        $this->id_message = $value;
    }

    public function getId_expediteur()
    {
        return $this->id_expediteur;
    }

    public function setId_expediteur($value)
    {
        $this->id_expediteur = $value;
    }

    public function getId_destinataire()
    {
        return $this->id_destinataire;
    }

    public function setId_destinataire($value)
    {
        $this->id_destinataire = $value;
    }

    public function getContenu()
    {
        return $this->contenu;
    }

    public function setContenu($value)
    {
        $this->contenu = $value;
    }

    public function getDate_envoi()
    {
        return $this->date_envoi;
    }

    public function setDate_envoi($value)
    {
        $this->date_envoi = $value;
    }

    public function getLu()
    {
        return $this->lu;
    }

    public function setLu($value)
    {
        $this->lu = $value;
    }

    public function getModifie()
    {
        return $this->modifie;
    }

    public function setModifie($value)
    {
        $this->modifie = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }
}
