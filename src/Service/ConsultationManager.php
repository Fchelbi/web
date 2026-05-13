<?php

namespace App\Service;

use App\Entity\ConsultationEnLigne;

class ConsultationManager
{
    public function validate(ConsultationEnLigne $consultation): bool
    {
        $date = $consultation->getDateConsultation();

        if (!$date instanceof \DateTimeInterface || $date <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('La date de consultation doit etre dans le futur.');
        }

        if ($consultation->getUser() === null) {
            throw new \InvalidArgumentException('L utilisateur est obligatoire.');
        }

        if (trim($consultation->getStatut()) === '') {
            throw new \InvalidArgumentException('Le statut est obligatoire.');
        }

        return true;
    }
}
