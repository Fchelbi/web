<?php

namespace App\Tests\Service;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use App\Service\ConsultationManager;
use PHPUnit\Framework\TestCase;

class ConsultationManagerTest extends TestCase
{
    private ConsultationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ConsultationManager();
    }

    public function testValidConsultation(): void
    {
        $consultation = $this->createValidConsultation();

        $this->assertTrue($this->manager->validate($consultation));
    }

    public function testInvalidDate(): void
    {
        $consultation = $this->createValidConsultation();
        $consultation->setDateConsultation(new \DateTimeImmutable('-1 day'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de consultation doit etre dans le futur.');

        $this->manager->validate($consultation);
    }

    public function testMissingUser(): void
    {
        $consultation = $this->createValidConsultation();
        $consultation->setUser(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L utilisateur est obligatoire.');

        $this->manager->validate($consultation);
    }

    public function testEmptyStatut(): void
    {
        $consultation = $this->createValidConsultation();
        $consultation->setStatut('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut est obligatoire.');

        $this->manager->validate($consultation);
    }

    public function testValidEdgeCase(): void
    {
        $consultation = $this->createValidConsultation();
        $consultation->setDateConsultation(new \DateTimeImmutable('+1 second'));

        $this->assertTrue($this->manager->validate($consultation));
    }

    public function testInvalidEdgeCase(): void
    {
        $consultation = $this->createValidConsultation();
        $consultation->setDateConsultation(new \DateTimeImmutable('now'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de consultation doit etre dans le futur.');

        $this->manager->validate($consultation);
    }

    private function createValidConsultation(): ConsultationEnLigne
    {
        $user = (new User())
            ->setPrenom('Ali')
            ->setNom('Ben Salah')
            ->setEmail('ali@example.com')
            ->setRole(User::ROLE_PATIENT);

        $psychologue = (new User())
            ->setPrenom('Sara')
            ->setNom('Coach')
            ->setEmail('sara@example.com')
            ->setRole(User::ROLE_COACH);

        return (new ConsultationEnLigne())
            ->setDateConsultation(new \DateTimeImmutable('+2 days'))
            ->setMotif('Gestion du stress')
            ->setStatut(ConsultationEnLigne::STATUT_EN_ATTENTE)
            ->setUser($user)
            ->setPsychologue($psychologue);
    }
}
