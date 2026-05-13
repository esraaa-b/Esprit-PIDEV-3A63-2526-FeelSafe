<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/admin/ia')]
#[IsGranted('ROLE_ADMIN')]
class IaProxyController extends AbstractController
{
    public function __construct(
        private string $groqApiKey,
        private LoggerInterface $logger
    ) {}

    /**
     * Proxy sécurisé vers l'API Groq (llama-3.3-70b).
     * Le navigateur ne voit jamais la clé API.
     */
    #[Route('/analyser', name: 'admin_ia_analyser', methods: ['POST'])]
    public function analyser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? '';

        if (empty($prompt)) {
            return new JsonResponse(['error' => 'Prompt vide'], 400);
        }

        if (empty($this->groqApiKey)) {
            return new JsonResponse(['error' => 'Clé API Groq non configurée dans .env (GROQ_API_KEY)'], 500);
        }

        try {
            $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->groqApiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model'      => 'llama-3.3-70b-versatile',
                    'max_tokens' => 1200,
                    'temperature' => 0.7,
                    'messages'   => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un expert en gestion de plateforme de santé mentale. Tu réponds toujours en français, de façon structurée et analytique.'
                        ],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]),
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \RuntimeException('Erreur réseau : ' . $curlError);
            }

            $decoded = json_decode($response, true);

            if ($httpCode !== 200) {
                $errMsg = $decoded['error']['message'] ?? ('Erreur API HTTP ' . $httpCode);
                throw new \RuntimeException($errMsg);
            }

            $rapport = $decoded['choices'][0]['message']['content'] ?? 'Aucun rapport généré.';

            return new JsonResponse(['rapport' => $rapport]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur proxy IA', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}