<?php

namespace App\Tests\Service;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AiServiceTest extends TestCase
{
    private AiService $aiService;
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->aiService = new AiService(
            $this->entityManager,
            $this->httpClient,
            $this->logger,
            null, // No API key, test fallback
        );
    }

    /**
     * Test: Get most available coach using fallback (no Gemini API)
     */
    public function testSuggestMostAvailablePsyFallback(): void
    {
        // Create mock coaches
        $coach1 = new User();
        $coach1->setId(1);
        $coach1->setPrenom('John');
        $coach1->setNom('Doe');
        $coach1->setRole(User::ROLE_COACH);

        $coach2 = new User();
        $coach2->setId(2);
        $coach2->setPrenom('Sarah');
        $coach2->setNom('Smith');
        $coach2->setRole(User::ROLE_COACH);

        // Mock the repository
        $mockRepository = $this->createMock(EntityRepository::class);
        $mockRepository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($coach2);

        // Mock EntityManager to return the repository
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepository);

        // Mock the query builder for getting coaches
        $mockQueryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQueryBuilder->method('select')->willReturnSelf();
        $mockQueryBuilder->method('from')->willReturnSelf();
        $mockQueryBuilder->method('leftJoin')->willReturnSelf();
        $mockQueryBuilder->method('where')->willReturnSelf();
        $mockQueryBuilder->method('andWhere')->willReturnSelf();
        $mockQueryBuilder->method('groupBy')->willReturnSelf();
        $mockQueryBuilder->method('orderBy')->willReturnSelf();
        $mockQueryBuilder->method('setParameter')->willReturnSelf();
        $mockQueryBuilder->method('getQuery')->willReturnSelf();

        // Return coaches data (coach2 has fewer consultations)
        $mockQueryBuilder->method('getResult')
            ->willReturn([
                ['id' => 1, 'name' => 'John Doe', 'consultations' => 5],
                ['id' => 2, 'name' => 'Sarah Smith', 'consultations' => 2],
            ]);

        // Mock the query builder creation
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($mockQueryBuilder);

        // Test: Get the most available coach
        $result = $this->aiService->suggestMostAvailablePsy();

        // Assert: Should return coach2 (Sarah) with lowest consultations
        $this->assertNotNull($result);
        $this->assertEquals(2, $result->getId());
        $this->assertEquals('Sarah', $result->getPrenom());
    }

    /**
     * Test: Return null when no coaches available
     */
    public function testSuggestMostAvailablePsyNoCoachesAvailable(): void
    {
        // Mock empty coaches list
        $mockQueryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQueryBuilder->method('select')->willReturnSelf();
        $mockQueryBuilder->method('from')->willReturnSelf();
        $mockQueryBuilder->method('leftJoin')->willReturnSelf();
        $mockQueryBuilder->method('where')->willReturnSelf();
        $mockQueryBuilder->method('groupBy')->willReturnSelf();
        $mockQueryBuilder->method('orderBy')->willReturnSelf();
        $mockQueryBuilder->method('setParameter')->willReturnSelf();
        $mockQueryBuilder->method('getQuery')->willReturnSelf();
        $mockQueryBuilder->method('getResult')->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $result = $this->aiService->suggestMostAvailablePsy();

        $this->assertNull($result);
    }

    /**
     * Test: Get coaches with consultation counts (public method)
     */
    public function testGetCoachesWithConsultationCounts(): void
    {
        $mockQueryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQueryBuilder->method('select')->willReturnSelf();
        $mockQueryBuilder->method('from')->willReturnSelf();
        $mockQueryBuilder->method('leftJoin')->willReturnSelf();
        $mockQueryBuilder->method('where')->willReturnSelf();
        $mockQueryBuilder->method('groupBy')->willReturnSelf();
        $mockQueryBuilder->method('orderBy')->willReturnSelf();
        $mockQueryBuilder->method('setParameter')->willReturnSelf();
        $mockQueryBuilder->method('getQuery')->willReturnSelf();

        $mockQueryBuilder->method('getResult')
            ->willReturn([
                ['id' => 1, 'name' => 'Coach 1', 'consultations' => 10],
                ['id' => 2, 'name' => 'Coach 2', 'consultations' => 3],
                ['id' => 3, 'name' => 'Coach 3', 'consultations' => 7],
            ]);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $result = $this->aiService->getCoachesWithConsultationCounts();

        $this->assertCount(3, $result);
        $this->assertEquals('Coach 1', $result[0]['name']);
        $this->assertEquals(10, $result[0]['consultations']);
    }

    /**
     * Test: Date filtering (fromDate parameter)
     */
    public function testSuggestMostAvailablePsyWithDateFilter(): void
    {
        $fromDate = new \DateTime('2026-04-25');

        $coach1 = new User();
        $coach1->setId(1);
        $coach1->setPrenom('Coach');
        $coach1->setNom('One');

        $mockRepository = $this->createMock(EntityRepository::class);
        $mockRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($coach1);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepository);

        $mockQueryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQueryBuilder->method('select')->willReturnSelf();
        $mockQueryBuilder->method('from')->willReturnSelf();
        $mockQueryBuilder->method('leftJoin')->willReturnSelf();
        $mockQueryBuilder->method('where')->willReturnSelf();
        $mockQueryBuilder->method('andWhere')->willReturnSelf();
        $mockQueryBuilder->method('groupBy')->willReturnSelf();
        $mockQueryBuilder->method('orderBy')->willReturnSelf();
        $mockQueryBuilder->method('setParameter')->willReturnSelf();
        $mockQueryBuilder->method('getQuery')->willReturnSelf();
        $mockQueryBuilder->method('getResult')
            ->willReturn([
                ['id' => 1, 'name' => 'Coach One', 'consultations' => 2],
            ]);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $result = $this->aiService->suggestMostAvailablePsy($fromDate);

        $this->assertNotNull($result);
        $this->assertEquals('Coach', $result->getPrenom());
    }

    /**
     * Test: Logging on coach selection
     */
    public function testLoggingOnCoachSelection(): void
    {
        $coach = new User();
        $coach->setId(1);
        $coach->setPrenom('Test');
        $coach->setNom('Coach');

        $mockRepository = $this->createMock(EntityRepository::class);
        $mockRepository->expects($this->once())
            ->method('find')
            ->willReturn($coach);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($mockRepository);

        $mockQueryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQueryBuilder->method('select')->willReturnSelf();
        $mockQueryBuilder->method('from')->willReturnSelf();
        $mockQueryBuilder->method('leftJoin')->willReturnSelf();
        $mockQueryBuilder->method('where')->willReturnSelf();
        $mockQueryBuilder->method('groupBy')->willReturnSelf();
        $mockQueryBuilder->method('orderBy')->willReturnSelf();
        $mockQueryBuilder->method('setParameter')->willReturnSelf();
        $mockQueryBuilder->method('getQuery')->willReturnSelf();
        $mockQueryBuilder->method('getResult')
            ->willReturn([
                ['id' => 1, 'name' => 'Test Coach', 'consultations' => 1],
            ]);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($mockQueryBuilder);

        // Expect logging
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Selected coach via fallback method', $this->anything());

        $this->aiService->suggestMostAvailablePsy();
    }
}
