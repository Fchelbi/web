<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;

#[ORM\Entity]
class Chat_history
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "chat_historys")]
    #[ORM\JoinColumn(name: 'id_patient', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $id_patient;

    #[ORM\Column(type: "string", length: 64)]
    private string $session_id;

    #[ORM\Column(type: "string", length: 16)]
    private string $role;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getId_patient()
    {
        return $this->id_patient;
    }

    public function setId_patient($value)
    {
        $this->id_patient = $value;
    }

    public function getSession_id()
    {
        return $this->session_id;
    }

    public function setSession_id($value)
    {
        $this->session_id = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($value)
    {
        $this->content = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }
}
