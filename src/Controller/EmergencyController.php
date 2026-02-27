<?php

namespace App\Controller;

use App\Entity\Urgence;
use App\Repository\UrgenceRepository;
use App\Service\EmergencyChatbotService;
use App\Service\EmergencyMailService; // ADD THIS LINE
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
    private EmergencyMailService $mailService; // ADD THIS PROPERTY

    // UPDATE CONSTRUCTOR
    public function __construct(
        EmergencyChatbotService $chatbot,
        EmergencyMailService $mailService // ADD THIS PARAMETER
    ) {
        $this->chatbot = $chatbot;
        $this->mailService = $mailService; // ADD THIS LINE
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

                $urgence->setTypeUrgence('User Report');
                $urgence->setDescription($description);
                $urgence->setLocation($location ?: 'Non spécifié');

                $severityMap = [
                    'high' => 5,
                    'medium' => 3,
                    'low' => 1
                ];

                $urgence->setSeverityLevel($severityMap[$urgencyLevel] ?? 3);
                $urgence->setStatus('Pending');
                $urgence->setCreatedAt(new \DateTime());
                $urgence->setUser($user);

                $entityManager->persist($urgence);
                $entityManager->flush();

                // SEND EMAIL NOTIFICATION TO ADMIN ONLY
<<<<<<< HEAD
                if (!$user instanceof \App\Entity\Utilisateur) {
                $logger->error('User is not an Utilisateur instance');
                throw new \LogicException('Expected Utilisateur');
            }
=======
>>>>>>> 1ebf808 (Import complet projet CopieBONNE (rdv / accompagnement))
                $this->mailService->sendEmergencyNotification($urgence, $user);

                // UPDATE SUCCESS MESSAGE
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
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('dashboard/emergency/index.html.twig', [
            'emergencies' => $userEmergencies,
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

    #[Route('/chat/send', name: 'chat_send', methods: ['POST'])]
    public function sendChat(Request $request, SessionInterface $session, LoggerInterface $logger): JsonResponse
    {
        try {
            // Verify user is authenticated
            $this->getCurrentUser($logger);

            $data = json_decode($request->getContent(), true);
            $userMessage = $data['message'] ?? '';

            if (empty($userMessage)) {
                return $this->json(['error' => 'Message cannot be empty'], Response::HTTP_BAD_REQUEST);
            }

            // Get conversation history from session
            $conversationHistory = $session->get('chat_history', []);

            // Detect crisis level
            $crisisLevel = $this->chatbot->detectCrisisLevel($userMessage);

            // Get bot response
            $response = $this->chatbot->generateResponse($userMessage, $conversationHistory);

            // Add messages to history
            $conversationHistory[] = ['role' => 'user', 'content' => $userMessage];
            $conversationHistory[] = ['role' => 'assistant', 'content' => $response['message']];

            // Keep only last 20 messages to prevent session bloat
            if (count($conversationHistory) > 20) {
                $conversationHistory = array_slice($conversationHistory, -20);
            }

            $session->set('chat_history', $conversationHistory);

            // Log chat for monitoring (optional)
            $logger->info('Chat message processed', [
                'user' => $this->getUser()->getUserIdentifier(),
                'crisis_level' => $crisisLevel
            ]);

            return $this->json([
                'success' => true,
                'message' => $response['message'],
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

            // Format for frontend display
            $formattedHistory = [];
            foreach ($history as $message) {
                $formattedHistory[] = [
                    'sender' => $message['role'] === 'user' ? 'user' : 'bot',
                    'text' => $message['content'],
                    'time' => '' // We don't store timestamps in session
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