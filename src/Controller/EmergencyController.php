<?php

namespace App\Controller;

use App\Entity\Urgence;
use App\Repository\UrgenceRepository;
use App\Service\EmergencyChatbotService;
use App\Service\EmergencyMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Psr\Log\LoggerInterface;

class EmergencyController extends AbstractController
{
    private EmergencyChatbotService $chatbot;
    private EmergencyMailService $mailService;

    // Crisis keywords matching Java's GeminiService
    private const CRITICAL_KEYWORDS = ['kill myself', 'suicide', 'end my life', 'want to die', 'hurt myself', 'take my life'];
    private const HIGH_KEYWORDS = ['abuse', 'assault', 'attack', 'weapon', 'bleeding', 'raped', 'beaten'];
    private const MEDIUM_KEYWORDS = ['panic attack', 'crisis', 'can\'t cope', 'overdose', 'self harm'];

    public function __construct(
        EmergencyChatbotService $chatbot,
        EmergencyMailService $mailService
    ) {
        $this->chatbot = $chatbot;
        $this->mailService = $mailService;
    }

    #[Route('/dashboard/emergency', name: 'app_emergency', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UrgenceRepository $urgenceRepository,
        LoggerInterface $logger
    ): Response {

        $user = $this->getCurrentUser($logger);

        $logger->info('User accessed emergency page', [
            'identifier' => $user->getUserIdentifier()
        ]);

        /* ===================== HANDLE FORM ===================== */

        if ($request->isMethod('POST') && $request->request->has('submit_emergency')) {

            try {
                $urgence = new Urgence();

                $description = $request->request->get('description');
                $urgencyLevel = $request->request->get('urgency_level');
                $location = $request->request->get('location');

                // Set type based on gravity (matching Java)
                $severityMap = [
                    'high' => 5,
                    'medium' => 3,
                    'low' => 1
                ];

                $gravity = $severityMap[$urgencyLevel] ?? 3;

                // Determine type based on gravity (matching Java)
                if ($gravity == 5) {
                    $type = 'Tentative de suicide';
                } elseif ($gravity == 4) {
                    $type = 'Violence/Agression';
                } else {
                    $type = 'Crise de panique';
                }

                $urgence->setTypeUrgence($type);
                $urgence->setDescription($description . ($location ? " | Localisation: " . $location : ""));
                $urgence->setNiveauGravite($gravity);
                $urgence->setStatut(Urgence::STATUT_EN_ATTENTE);
                $urgence->setDateHeure(new \DateTime());
                $urgence->setIdUtilisateur($user->getId());

                $entityManager->persist($urgence);
                $entityManager->flush();

                // SEND EMAIL NOTIFICATION TO ADMIN ONLY
                if (!$user instanceof \App\Entity\Utilisateur) {
                    $logger->error('User is not an Utilisateur instance');
                    throw new \LogicException('Expected Utilisateur');
                }
                $this->mailService->sendEmergencyNotification($urgence, $user);

                $this->addFlash('success', 'Votre demande d\'urgence a été envoyée avec succès! Une confirmation vous a été envoyée par email.');

            } catch (\Throwable $e) {

                $logger->error('Emergency save failed', [
                    'error' => $e->getMessage()
                ]);

                $this->addFlash('error', 'Une erreur est survenue.');
            }

            return $this->redirectToRoute('app_emergency');
        }

        /* ===================== LIST USER EMERGENCIES ===================== */

        $userEmergencies = $urgenceRepository->findBy(
            ['idUtilisateur' => $user->getId()],
            ['dateHeure' => 'DESC']
        );

        $hasEmergencies = count($userEmergencies) > 0;

        return $this->render('dashboard/emergency/index.html.twig', [
            'emergencies' => $userEmergencies,
            'hasEmergencies' => $hasEmergencies,
        ]);
    }

    #[Route('/emergency/history', name: 'emergency_history', methods: ['GET'])]
    public function history(UrgenceRepository $urgenceRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $emergencies = $urgenceRepository->findBy(
            ['idUtilisateur' => $user->getId()],
            ['dateHeure' => 'DESC']
        );

        return $this->render('dashboard/emergency/history.html.twig', [
            'emergencies' => $emergencies
        ]);
    }

    /**
     * Always returns authenticated user
     */
    private function getCurrentUser(LoggerInterface $logger): UserInterface
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            $logger->warning('Anonymous access blocked');
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    // ===================== CHAT METHODS =====================

    /**
     * Detect crisis level from message (matching Java GeminiService)
     */
    private function detectCrisisLevel(string $message): int
    {
        $lowerMsg = strtolower($message);

        foreach (self::CRITICAL_KEYWORDS as $keyword) {
            if (strpos($lowerMsg, $keyword) !== false) {
                return 5;
            }
        }

        foreach (self::HIGH_KEYWORDS as $keyword) {
            if (strpos($lowerMsg, $keyword) !== false) {
                return 4;
            }
        }

        foreach (self::MEDIUM_KEYWORDS as $keyword) {
            if (strpos($lowerMsg, $keyword) !== false) {
                return 3;
            }
        }

        return 0;
    }

    /**
     * Create urgency from chat detection (matching Java)
     */
    private function createUrgencyFromChat(string $message, int $gravity, int $userId, EntityManagerInterface $entityManager, LoggerInterface $logger): ?Urgence
    {
        try {
            // Determine type based on gravity
            if ($gravity == 5) {
                $type = 'Tentative de suicide';
            } elseif ($gravity == 4) {
                $type = 'Violence/Agression';
            } else {
                $type = 'Crise de panique';
            }

            $urgence = new Urgence();
            $urgence->setTypeUrgence($type);
            $urgence->setDescription("Message: \"" . substr($message, 0, 500) . "\"");
            $urgence->setNiveauGravite($gravity);
            $urgence->setStatut(Urgence::STATUT_EN_ATTENTE);
            $urgence->setDateHeure(new \DateTime());
            $urgence->setIdUtilisateur($userId);

            $entityManager->persist($urgence);
            $entityManager->flush();

            $logger->info('Urgence créée automatiquement via chat', [
                'user_id' => $userId,
                'gravity' => $gravity,
                'type' => $type
            ]);

            return $urgence;

        } catch (\Exception $e) {
            $logger->error('Failed to create urgency from chat: ' . $e->getMessage());
            return null;
        }
    }

    #[Route('/chat/send', name: 'chat_send', methods: ['POST'])]
    public function sendChat(Request $request, SessionInterface $session, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        try {
            $user = $this->getCurrentUser($logger);
            $userId = $user->getId();

            $data = json_decode($request->getContent(), true);
            $userMessage = $data['message'] ?? '';

            if (empty($userMessage)) {
                return $this->json(['error' => 'Message cannot be empty'], Response::HTTP_BAD_REQUEST);
            }

            // Detect crisis level (matching Java)
            $crisisLevel = $this->detectCrisisLevel($userMessage);

            // Create urgency if crisis detected (matching Java GeminiService)
            if ($crisisLevel > 0) {
                $this->createUrgencyFromChat($userMessage, $crisisLevel, $userId, $entityManager, $logger);
            }

            $conversationHistory = $session->get('chat_history', []);

            // Get response from Gemini (already in your service)
            $response = $this->chatbot->generateResponse($userMessage, $conversationHistory);

            $conversationHistory[] = ['role' => 'user', 'content' => $userMessage];
            $conversationHistory[] = ['role' => 'assistant', 'content' => $response['message']];

            if (count($conversationHistory) > 20) {
                $conversationHistory = array_slice($conversationHistory, -20);
            }

            $session->set('chat_history', $conversationHistory);

            // Customize response based on crisis level (matching Java)
            $responseMessage = $response['message'];
            if ($crisisLevel == 5) {
                $responseMessage = "🚨 **URGENCE CRITIQUE** - Une urgence niveau 5 a été créée. Un administrateur va vous contacter immédiatement. Restez en ligne, vous n'êtes pas seul(e). 💚\n\n" . $responseMessage;
            } elseif ($crisisLevel == 4) {
                $responseMessage = "⚠️ **SITUATION GRAVE** - Une urgence niveau 4 a été créée. Un administrateur vous contactera rapidement. Je suis là pour vous écouter.\n\n" . $responseMessage;
            } elseif ($crisisLevel == 3) {
                $responseMessage = "🆘 **CRISE DÉTECTÉE** - Une urgence niveau 3 a été enregistrée. Parlez-moi de ce que vous ressentez, je suis là pour vous aider.\n\n" . $responseMessage;
            }

            $logger->info('Chat message processed', [
                'user' => $user->getUserIdentifier(),
                'crisis_level' => $crisisLevel
            ]);

            return $this->json([
                'success' => true,
                'message' => $responseMessage,
                'crisis_level' => $crisisLevel,
                'timestamp' => (new \DateTime())->format('H:i:s')
            ]);

        } catch (\Exception $e) {
            $logger->error('Chat error: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Unable to process your message. Please try again.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/chat/clear', name: 'chat_clear', methods: ['POST'])]
    public function clearChat(SessionInterface $session, LoggerInterface $logger): JsonResponse
    {
        try {
            $this->getCurrentUser($logger);
            $session->remove('chat_history');

            return $this->json([
                'success' => true,
                'message' => 'Chat history cleared'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Unable to clear chat'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/chat/history', name: 'chat_history', methods: ['GET'])]
    public function getChatHistory(SessionInterface $session, LoggerInterface $logger): JsonResponse
    {
        try {
            $this->getCurrentUser($logger);

            $history = $session->get('chat_history', []);

            $formattedHistory = [];
            foreach ($history as $message) {
                $formattedHistory[] = [
                    'sender' => $message['role'] === 'user' ? 'user' : 'bot',
                    'text' => $message['content'],
                    'time' => ''
                ];
            }

            return $this->json([
                'success' => true,
                'history' => $formattedHistory
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'history' => []
            ]);
        }
    }
}