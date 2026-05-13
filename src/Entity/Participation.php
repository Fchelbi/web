<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Formation;

#[ORM\Entity]
class Participation
{

   #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: "integer")]
private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private User $user_id;

        #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: "participations")]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Formation $formation_id;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_inscription;

    public function getId(): ?int { return $this->id; }
public function setId(int $value): self { $this->id = $value; return $this; }
public function getUser_id(): User { return $this->user_id; }
public function setUser_id(User $value): self { $this->user_id = $value; return $this; }
public function getFormation_id(): Formation { return $this->formation_id; }
public function setFormation_id(Formation $value): self { $this->formation_id = $value; return $this; }
public function getDate_inscription(): \DateTimeInterface { return $this->date_inscription; }
public function setDate_inscription(\DateTimeInterface $value): self { $this->date_inscription = $value; return $this; }
}
