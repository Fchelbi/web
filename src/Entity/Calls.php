<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;

#[ORM\Entity]
class Calls
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_call;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "callss")]
    #[ORM\JoinColumn(name: 'id_caller', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $id_caller;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "callss")]
    #[ORM\JoinColumn(name: 'id_receiver', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $id_receiver;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_appel;

    #[ORM\Column(type: "integer")]
    private int $duree_secondes;

    #[ORM\Column(type: "string", length: 50)]
    private string $caller_ip;

    #[ORM\Column(type: "integer")]
    private int $caller_port;

    public function getId_call()
    {
        return $this->id_call;
    }

    public function setId_call($value)
    {
        $this->id_call = $value;
    }

    public function getId_caller()
    {
        return $this->id_caller;
    }

    public function setId_caller($value)
    {
        $this->id_caller = $value;
    }

    public function getId_receiver()
    {
        return $this->id_receiver;
    }

    public function setId_receiver($value)
    {
        $this->id_receiver = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getDate_appel()
    {
        return $this->date_appel;
    }

    public function setDate_appel($value)
    {
        $this->date_appel = $value;
    }

    public function getDuree_secondes()
    {
        return $this->duree_secondes;
    }

    public function setDuree_secondes($value)
    {
        $this->duree_secondes = $value;
    }

    public function getCaller_ip()
    {
        return $this->caller_ip;
    }

    public function setCaller_ip($value)
    {
        $this->caller_ip = $value;
    }

    public function getCaller_port()
    {
        return $this->caller_port;
    }

    public function setCaller_port($value)
    {
        $this->caller_port = $value;
    }
}
