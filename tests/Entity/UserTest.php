<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testSetAndGetNom(): void
    {
        $this->user->setNom('Dupont');
        $this->assertEquals('Dupont', $this->user->getNom());
    }

    public function testSetAndGetPrenom(): void
    {
        $this->user->setPrenom('Jean');
        $this->assertEquals('Jean', $this->user->getPrenom());
    }

    public function testSetAndGetEmail(): void
    {
        $this->user->setEmail('jean@test.com');
        $this->assertEquals('jean@test.com', $this->user->getEmail());
    }

    public function testSetAndGetRole(): void
    {
        $this->user->setRole('Patient');
        $this->assertEquals('Patient', $this->user->getRole());
    }

    public function testGetRolesReturnsCorrectFormat(): void
    {
        $this->user->setRole('Admin');
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testGetRolesPatient(): void
    {
        $this->user->setRole('Patient');
        $this->assertContains('ROLE_PATIENT', $this->user->getRoles());
    }

    public function testGetRolesCoach(): void
    {
        $this->user->setRole('Coach');
        $this->assertContains('ROLE_COACH', $this->user->getRoles());
    }

    public function testIsVerifiedDefaultFalse(): void
    {
        $this->assertFalse($this->user->isVerified());
    }

    public function testSetIsVerified(): void
    {
        $this->user->setIsVerified(true);
        $this->assertTrue($this->user->isVerified());
    }

    public function testIsBannedDefaultFalse(): void
    {
        $this->assertFalse($this->user->isBanned());
    }

    public function testSetIsBanned(): void
    {
        $this->user->setBanned(true);
        $this->assertTrue($this->user->isBanned());
    }

    public function testBadWordsCountDefaultZero(): void
    {
        $this->assertEquals(0, $this->user->getBadWordsCount());
    }

    public function testIncrementBadWordsCount(): void
    {
        $this->user->incrementBadWordsCount();
        $this->assertEquals(1, $this->user->getBadWordsCount());
    }

    public function testToString(): void
    {
        $this->user->setPrenom('Jean');
        $this->user->setNom('Dupont');
        $this->assertEquals('Jean Dupont', (string)$this->user);
    }

    public function testGetUserIdentifier(): void
    {
        $this->user->setEmail('jean@test.com');
        $this->assertEquals('jean@test.com', $this->user->getUserIdentifier());
    }

    public function testSetAndGetNumTel(): void
    {
        $this->user->setNumTel('12345678');
        $this->assertEquals('12345678', $this->user->getNumTel());
    }
}