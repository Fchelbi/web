<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Entity\Rapport;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_user;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "string", length: 100)]
    private string $prenom;

    #[ORM\Column(type: "string", length: 150)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $mdp;

    #[ORM\Column(type: "string")]
    private string $role;

    #[ORM\Column(type: "string", length: 20)]
    private string $num_tel;

    #[ORM\Column(type: "string", length: 500)]
    private string $photo;

    // ---- Getters & Setters for basic fields ----

    public function getId_user() { return $this->id_user; }
    public function setId_user($value) { $this->id_user = $value; }

    public function getNom() { return $this->nom; }
    public function setNom($value) { $this->nom = $value; }

    public function getPrenom() { return $this->prenom; }
    public function setPrenom($value) { $this->prenom = $value; }

    public function getEmail() { return $this->email; }
    public function setEmail($value) { $this->email = $value; }

    public function getMdp() { return $this->mdp; }
    public function setMdp($value) { $this->mdp = $value; }

    public function getRole() { return $this->role; }
    public function setRole($value) { $this->role = $value; }

    public function getNum_tel() { return $this->num_tel; }
    public function setNum_tel($value) { $this->num_tel = $value; }

    public function getPhoto() { return $this->photo; }
    public function setPhoto($value) { $this->photo = $value; }

    // ---- Chat History ----

    #[ORM\OneToMany(mappedBy: "id_patient", targetEntity: Chat_history::class)]
    private Collection $chat_historys;

    public function getChat_historys(): Collection { return $this->chat_historys; }

    public function addChat_history(Chat_history $chat_history): self
    {
        if (!$this->chat_historys->contains($chat_history)) {
            $this->chat_historys[] = $chat_history;
            $chat_history->setId_patient($this);
        }
        return $this;
    }

    public function removeChat_history(Chat_history $chat_history): self
    {
        if ($this->chat_historys->removeElement($chat_history)) {
            if ($chat_history->getId_patient() === $this) {
                $chat_history->setId_patient(null);
            }
        }
        return $this;
    }

    // ---- Calls as Caller ----

    #[ORM\OneToMany(mappedBy: "id_caller", targetEntity: Calls::class)]
    private Collection $callsAsCaller;

    public function getCallsAsCaller(): Collection { return $this->callsAsCaller; }

    public function addCallsAsCaller(Calls $calls): self
    {
        if (!$this->callsAsCaller->contains($calls)) {
            $this->callsAsCaller[] = $calls;
            $calls->setId_caller($this);
        }
        return $this;
    }

    public function removeCallsAsCaller(Calls $calls): self
    {
        if ($this->callsAsCaller->removeElement($calls)) {
            if ($calls->getId_caller() === $this) {
                $calls->setId_caller(null);
            }
        }
        return $this;
    }

    // ---- Calls as Receiver ----

    #[ORM\OneToMany(mappedBy: "id_receiver", targetEntity: Calls::class)]
    private Collection $callsAsReceiver;

    public function getCallsAsReceiver(): Collection { return $this->callsAsReceiver; }

    public function addCallsAsReceiver(Calls $calls): self
    {
        if (!$this->callsAsReceiver->contains($calls)) {
            $this->callsAsReceiver[] = $calls;
            $calls->setId_receiver($this);
        }
        return $this;
    }

    public function removeCallsAsReceiver(Calls $calls): self
    {
        if ($this->callsAsReceiver->removeElement($calls)) {
            if ($calls->getId_receiver() === $this) {
                $calls->setId_receiver(null);
            }
        }
        return $this;
    }

    // ---- Messages as Sender ----

    #[ORM\OneToMany(mappedBy: "id_expediteur", targetEntity: Messages::class)]
    private Collection $messagesAsSender;

    public function getMessagesAsSender(): Collection { return $this->messagesAsSender; }

    public function addMessagesAsSender(Messages $messages): self
    {
        if (!$this->messagesAsSender->contains($messages)) {
            $this->messagesAsSender[] = $messages;
            $messages->setId_expediteur($this);
        }
        return $this;
    }

    public function removeMessagesAsSender(Messages $messages): self
    {
        if ($this->messagesAsSender->removeElement($messages)) {
            if ($messages->getId_expediteur() === $this) {
                $messages->setId_expediteur(null);
            }
        }
        return $this;
    }

    // ---- Messages as Receiver ----

    #[ORM\OneToMany(mappedBy: "id_destinataire", targetEntity: Messages::class)]
    private Collection $messagesAsReceiver;

    public function getMessagesAsReceiver(): Collection { return $this->messagesAsReceiver; }

    public function addMessagesAsReceiver(Messages $messages): self
    {
        if (!$this->messagesAsReceiver->contains($messages)) {
            $this->messagesAsReceiver[] = $messages;
            $messages->setId_destinataire($this);
        }
        return $this;
    }

    public function removeMessagesAsReceiver(Messages $messages): self
    {
        if ($this->messagesAsReceiver->removeElement($messages)) {
            if ($messages->getId_destinataire() === $this) {
                $messages->setId_destinataire(null);
            }
        }
        return $this;
    }

    // ---- Participations ----

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: Participation::class)]
    private Collection $participations;

    public function getParticipations(): Collection { return $this->participations; }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setUser_id($this);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getUser_id() === $this) {
                $participation->setUser_id(null);
            }
        }
        return $this;
    }

    // ---- Rapports as Patient ----

    #[ORM\OneToMany(mappedBy: "id_patient", targetEntity: Rapport::class)]
    private Collection $rapportsAsPatient;

    public function getRapportsAsPatient(): Collection { return $this->rapportsAsPatient; }

    public function addRapportsAsPatient(Rapport $rapport): self
    {
        if (!$this->rapportsAsPatient->contains($rapport)) {
            $this->rapportsAsPatient[] = $rapport;
            $rapport->setId_patient($this);
        }
        return $this;
    }

    public function removeRapportsAsPatient(Rapport $rapport): self
    {
        if ($this->rapportsAsPatient->removeElement($rapport)) {
            if ($rapport->getId_patient() === $this) {
                $rapport->setId_patient(null);
            }
        }
        return $this;
    }

    // ---- Rapports as Coach ----

    #[ORM\OneToMany(mappedBy: "id_coach", targetEntity: Rapport::class)]
    private Collection $rapportsAsCoach;

    public function getRapportsAsCoach(): Collection { return $this->rapportsAsCoach; }

    public function addRapportsAsCoach(Rapport $rapport): self
    {
        if (!$this->rapportsAsCoach->contains($rapport)) {
            $this->rapportsAsCoach[] = $rapport;
            $rapport->setId_coach($this);
        }
        return $this;
    }

    public function removeRapportsAsCoach(Rapport $rapport): self
    {
        if ($this->rapportsAsCoach->removeElement($rapport)) {
            if ($rapport->getId_coach() === $this) {
                $rapport->setId_coach(null);
            }
        }
        return $this;
    }
}