<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EmergencyChatbotService
{
    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;
    private string $geminiApiKey;
    private string $geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct(
        LoggerInterface $logger,
        HttpClientInterface $httpClient,
        string $geminiApiKey
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->geminiApiKey = $geminiApiKey;
    }

    public function generateResponse(string $userMessage, array $conversationHistory = []): array
    {
        // First, check for crisis using local detection (faster and more reliable)
        $crisisLevel = $this->detectCrisisLevel($userMessage);

        // If it's a HIGH crisis, we might want to add a safety reminder regardless of AI response
        $needsSafetyReminder = ($crisisLevel === 'HIGH');

        try {
            $this->logger->info('Sending message to Gemini API', [
                'message_length' => strlen($userMessage),
                'crisis_level' => $crisisLevel
            ]);

            // Build the conversation context
            $systemPrompt = "You are an empathetic emergency support assistant for FeelSafe, a personal safety app. 

IMPORTANT GUIDELINES:
- Be calm, compassionate, and supportive
- Keep responses concise (2-3 sentences max)
- If someone mentions self-harm, suicide, or immediate danger, urge them to call emergency services (15, 17, 112) immediately
- Never give medical advice
- Respond in the same language the user writes in (French, English, or Arabic)
- Be warm and human in your responses
- If you're unsure about something, it's okay to ask clarifying questions";

            // Build the conversation history into the prompt
            $fullPrompt = $systemPrompt . "\n\n";

            // Add recent conversation history for context
            if (!empty($conversationHistory)) {
                $fullPrompt .= "Previous conversation:\n";
                // Take last 3 exchanges (6 messages) for context
                $recentHistory = array_slice($conversationHistory, -6);
                foreach ($recentHistory as $msg) {
                    $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
                    $fullPrompt .= $role . ": " . $msg['content'] . "\n";
                }
                $fullPrompt .= "\n";
            }

            // Add current message
            $fullPrompt .= "User: " . $userMessage . "\n\nAssistant:";

            // Call Gemini API
            $response = $this->httpClient->request('POST', $this->geminiApiUrl . '?key=' . $this->geminiApiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $fullPrompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topP' => 0.8,
                        'topK' => 10
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_HATE_SPEECH',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ]
                    ]
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            $this->logger->info('Gemini API response received', [
                'status' => $statusCode
            ]);

            if ($statusCode !== 200) {
                $errorMsg = $content['error']['message'] ?? 'Unknown error';
                throw new \Exception('Gemini API error: ' . $errorMsg);
            }

            // Extract the response text
            $botResponse = $content['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Add safety reminder for high crisis situations if not already present
            if ($needsSafetyReminder && strpos(strtolower($botResponse), '15') === false) {
                $botResponse .= "\n\n🚨 If you're in immediate danger, please call 15 (SAMU), 17 (Police), or 112 right away.";
            }

            return [
                'success' => true,
                'message' => trim($botResponse),
                'crisis_level' => $crisisLevel,
                'usage' => null
            ];

        } catch (\Exception $e) {
            $this->logger->error('Gemini API error: ' . $e->getMessage());

            // Fallback to simple response system
            $this->logger->info('Falling back to simple response system');
            $fallbackResponse = $this->getSimpleResponse($userMessage);

            // Add safety reminder for high crisis in fallback
            if ($crisisLevel === 'HIGH') {
                $fallbackResponse .= "\n\n🚨 If you're in immediate danger, please call 15 (SAMU), 17 (Police), or 112 right away.";
            }

            return [
                'success' => true,
                'message' => $fallbackResponse,
                'crisis_level' => $crisisLevel,
                'usage' => null
            ];
        }
    }

    private function getSimpleResponse(string $message): string
    {
        $message = strtolower($message);

        // Emergency keywords
        if (
            strpos($message, 'suicide') !== false ||
            strpos($message, 'kill') !== false ||
            strpos($message, 'hurt myself') !== false
        ) {
            return "I'm very concerned about what you're sharing. Please reach out for immediate help:\n" .
                "• SAMU (medical emergencies): 15\n" .
                "• Police: 17\n" .
                "• European emergency: 112\n" .
                "You're not alone, and help is available 24/7.";
        }

        // Greetings
        if (
            strpos($message, 'bonjour') !== false ||
            strpos($message, 'salut') !== false ||
            strpos($message, 'hello') !== false ||
            strpos($message, 'hi') !== false
        ) {
            return "Bonjour! Comment puis-je vous aider aujourd'hui? Si vous êtes en situation d'urgence, n'hésitez pas à appeler le 15 ou le 112.";
        }

        // Anxiety/stress
        if (
            strpos($message, 'anxieux') !== false ||
            strpos($message, 'stress') !== false ||
            strpos($message, 'peur') !== false ||
            strpos($message, 'anxious') !== false
        ) {
            return "Je comprends que vous vous sentez anxieux. Prenez une grande respiration. Voulez-vous me parler de ce qui vous préoccupe?";
        }

        // Location/help
        if (
            strpos($message, 'aide') !== false ||
            strpos($message, 'help') !== false ||
            strpos($message, 'besoin') !== false
        ) {
            return "Je suis là pour vous aider. Pouvez-vous me décrire votre situation? Si c'est une urgence, appelez immédiatement le 15 ou le 112.";
        }

        // Default response with some variety
        $defaultResponses = [
            "Je vous écoute. Pouvez-vous m'en dire plus sur ce que vous ressentez?",
            "Comment vous sentez-vous en ce moment?",
            "Je suis là pour vous. Que souhaitez-vous partager?",
            "Parlez-moi de ce qui vous préoccupe."
        ];

        return $defaultResponses[array_rand($defaultResponses)];
    }

    public function detectCrisisLevel(string $message): string
    {
        $highRiskKeywords = ['suicide', 'kill myself', 'end my life', 'hurt myself', 'die', 'bleeding', 'attack', 'weapon'];
        $mediumRiskKeywords = ['scared', 'afraid', 'pain', 'worried', 'unsafe', 'threat', 'danger'];

        foreach ($highRiskKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return 'HIGH';
            }
        }

        foreach ($mediumRiskKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return 'MEDIUM';
            }
        }

        return 'LOW';
    }
}