<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use App\Service\EmergencyChatbotService;
use App\Service\EmergencyMailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UrgenceRepository;
use App\Controller\EmergencyController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmergencyControllerTest extends TestCase
{
    private $chatbot;
    private $mailService;
    private $logger;
    private $entityManager;
    private $repository;
    private $user;

    protected function setUp(): void
    {
        $this->chatbot = $this->createMock(EmergencyChatbotService::class);
        $this->mailService = $this->createMock(EmergencyMailService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UrgenceRepository::class);

        $this->user = $this->createMock(Utilisateur::class);
        $this->user->method('getUserIdentifier')->willReturn('test@example.com');
    }

    public function testIndexGetRequest(): void
    {
        $controller = $this->getMockBuilder(EmergencyController::class)
            ->setConstructorArgs([$this->chatbot, $this->mailService])
            ->onlyMethods(['getUser', 'render'])
            ->getMock();

        $controller->method('getUser')->willReturn($this->user);
        $this->repository->method('findBy')->willReturn([]);

        // Inject fake container
        $container = $this->createMock(ContainerInterface::class);
        $controller->setContainer($container);

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET']);

        $response = $controller->index(
            $request,
            $this->entityManager,
            $this->repository,
            $this->logger
        );

        $this->assertNotNull($response);
    }

    public function testGetCurrentUserWithValidUser(): void
    {
        $controller = $this->getMockBuilder(EmergencyController::class)
            ->setConstructorArgs([$this->chatbot, $this->mailService])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->method('getUser')->willReturn($this->user);

        $container = $this->createMock(ContainerInterface::class);
        $controller->setContainer($container);

        $method = new \ReflectionMethod($controller, 'getCurrentUser');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $this->logger);

        $this->assertSame($this->user, $result);
    }

    public function testGetCurrentUserThrowsExceptionWhenNoUser(): void
    {
        $controller = $this->getMockBuilder(EmergencyController::class)
            ->setConstructorArgs([$this->chatbot, $this->mailService])
            ->onlyMethods(['getUser', 'createAccessDeniedException'])
            ->getMock();

        $controller->method('getUser')->willReturn(null);
        $controller->method('createAccessDeniedException')
            ->willThrowException(new AccessDeniedException());

        $container = $this->createMock(ContainerInterface::class);
        $controller->setContainer($container);

        $method = new \ReflectionMethod($controller, 'getCurrentUser');
        $method->setAccessible(true);

        $this->expectException(AccessDeniedException::class);

        $method->invoke($controller, $this->logger);
    }

    public function testClearChat(): void
    {
        $controller = $this->getMockBuilder(EmergencyController::class)
            ->setConstructorArgs([$this->chatbot, $this->mailService])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->method('getUser')->willReturn($this->user);

        // Inject fake container
        $container = $this->createMock(ContainerInterface::class);
        $controller->setContainer($container);

        $session = new Session(new MockArraySessionStorage());
        $session->set('chat_history', ['test' => 'data']);

        $response = $controller->clearChat($session, $this->logger);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($session->get('chat_history', []));
    }
}