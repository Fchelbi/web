<?php

namespace App\Tests\Service;

use App\Service\BadWordsService;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BadWordsServiceTest extends TestCase
{
    private BadWordsService $service;

    protected function setUp(): void
    {
        $em   = $this->createMock(EntityManagerInterface::class);
        $mail = $this->createMock(MailService::class);
        $this->service = new BadWordsService($em, $mail);
    }

    public function testCleanMessageContainsNoBadWords(): void
    {
        $this->assertFalse($this->service->containsBadWords('Bonjour comment allez vous ?'));
    }

    public function testFrenchBadWordDetected(): void
    {
        $this->assertTrue($this->service->containsBadWords('putain c est nul'));
    }

    public function testEnglishBadWordDetected(): void
    {
        $this->assertTrue($this->service->containsBadWords('what the fuck is this'));
    }

    public function testMixedTextWithBadWord(): void
    {
        $this->assertTrue($this->service->containsBadWords('Je suis tellement merde aujourd hui'));
    }

    public function testEmptyStringContainsNoBadWords(): void
    {
        $this->assertFalse($this->service->containsBadWords(''));
    }

    public function testGetBadWordsFoundReturnsCorrectWords(): void
    {
        $found = $this->service->getBadWordsFound('putain c est nul');
        $this->assertContains('putain', $found);
    }

    public function testGetBadWordsFoundReturnsEmptyForCleanText(): void
    {
        $found = $this->service->getBadWordsFound('Bonjour tout le monde');
        $this->assertEmpty($found);
    }

    public function testUpperCaseBadWordDetected(): void
    {
        $this->assertTrue($this->service->containsBadWords('PUTAIN c est nul'));
    }
}