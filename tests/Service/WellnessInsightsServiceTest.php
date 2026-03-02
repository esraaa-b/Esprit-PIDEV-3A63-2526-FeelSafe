<?php

namespace App\Tests\Service;

use App\Entity\SessionActivite;
use App\Entity\Utilisateur;
use App\Enum\StatutSession;
use App\Repository\SessionActiviteRepository;
use App\Service\WellnessInsightsService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class WellnessInsightsServiceTest extends TestCase
{
    public function testGetInsightsWithNoSessions(): void
    {
        // Créer les mocks nécessaires
        $repository = $this->createMock(SessionActiviteRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        // Simuler le QueryBuilder
        $repository->method('createQueryBuilder')
            ->with('s')
            ->willReturn($queryBuilder);

        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Simuler aucun résultat
        $query->method('getResult')->willReturn([]);

        $user = new Utilisateur();
        $service = new WellnessInsightsService($repository);
        $result = $service->getInsights($user);

        // Vérifier le message par défaut
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('🌟', $result[0]['icon']);
        $this->assertStringContainsString('première session', $result[0]['message']);
    }

    public function testAnalyzeWeeklyPerformanceWithNoSessions(): void
    {
        $repository = $this->createMock(SessionActiviteRepository::class);
        $service = new WellnessInsightsService($repository);

        // Utiliser reflection pour tester la méthode privée
        $method = new \ReflectionMethod($service, 'analyzeWeeklyPerformance');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);

        // Vérifier que c'est un array (pas de null)
        $this->assertIsArray($result);
        $this->assertEquals('💪', $result['icon']);
        $this->assertEquals('warning', $result['type']);
    }

    public function testCalculateStreakWithOneDay(): void
    {
        $repository = $this->createMock(SessionActiviteRepository::class);
        $service = new WellnessInsightsService($repository);

        // Créer une session fictive avec une date
        $session = $this->createMock(SessionActivite::class);
        $session->method('getDateDebut')->willReturn(new \DateTime());
        $session->method('getStatutSession')->willReturn(StatutSession::COMPLETEE);

        $method = new \ReflectionMethod($service, 'calculateStreak');
        $method->setAccessible(true);

        $streak = $method->invoke($service, [$session]);

        $this->assertIsInt($streak);
    }

    public function testGetStreakMessage(): void
    {
        $repository = $this->createMock(SessionActiviteRepository::class);
        $service = new WellnessInsightsService($repository);

        $method = new \ReflectionMethod($service, 'getStreakMessage');
        $method->setAccessible(true);

        $message7 = $method->invoke($service, 7);
        $this->assertStringContainsString('7', $message7);

        $message30 = $method->invoke($service, 30);
        $this->assertStringContainsString('30', $message30);
    }
}