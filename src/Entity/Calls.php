<?php

namespace App\Entity;

use App\Repository\CallsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallsRepository::class)]
#[ORM\Table(name: '`calls`')]
class Calls
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_call = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_caller = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_receiver = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_appel = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duree_secondes = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $caller_ip = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $caller_port = null;

    public function getId_call(): ?int
    {
        return $this->id_call;
    }

    public function setId_call(?int $id_call): self
    {
        $this->id_call = $id_call;
        return $this;
    }

    public function getId_caller(): ?int
    {
        return $this->id_caller;
    }

    public function setId_caller(?int $id_caller): self
    {
        $this->id_caller = $id_caller;
        return $this;
    }

    public function getId_receiver(): ?int
    {
        return $this->id_receiver;
    }

    public function setId_receiver(?int $id_receiver): self
    {
        $this->id_receiver = $id_receiver;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDate_appel(): ?\DateTimeInterface
    {
        return $this->date_appel;
    }

    public function setDate_appel(?\DateTimeInterface $date_appel): self
    {
        $this->date_appel = $date_appel;
        return $this;
    }

    public function getDuree_secondes(): ?int
    {
        return $this->duree_secondes;
    }

    public function setDuree_secondes(?int $duree_secondes): self
    {
        $this->duree_secondes = $duree_secondes;
        return $this;
    }

    public function getCaller_ip(): ?string
    {
        return $this->caller_ip;
    }

    public function setCaller_ip(?string $caller_ip): self
    {
        $this->caller_ip = $caller_ip;
        return $this;
    }

    public function getCaller_port(): ?int
    {
        return $this->caller_port;
    }

    public function setCaller_port(?int $caller_port): self
    {
        $this->caller_port = $caller_port;
        return $this;
    }

}
