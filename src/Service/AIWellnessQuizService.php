<?php

namespace App\Service;

use App\Entity\ActiviteBienEtre;
use App\Repository\ActiviteBienEtreRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIWellnessQuizService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ActiviteBienEtreRepository $activiteRepo,
        private string $geminiApiKey
    ) {
    }

    /**
     * Questions du quiz
     */
    public function getQuizQuestions(): array
    {
        return [
            [
                'id' => 'mood',
                'question' => 'Comment vous sentez-vous en ce moment ?',
                'options' => [
                    ['value' => 'stressed', 'label' => '😰 Stressé(e) / Anxieux(se)', 'emoji' => '😰'],
                    ['value' => 'tired', 'label' => '😴 Fatigué(e) / Épuisé(e)', 'emoji' => '😴'],
                    ['value' => 'neutral', 'label' => '😐 Neutre / Ordinaire', 'emoji' => '😐'],
                    ['value' => 'good', 'label' => '😊 Bien / Positif(ve)', 'emoji' => '😊'],
                ]
            ],
            [
                'id' => 'energy',
                'question' => 'Quel est votre niveau d\'énergie actuel ?',
                'options' => [
                    ['value' => 'very_low', 'label' => '🔋 Très bas - J\'ai besoin de repos', 'emoji' => '🔋'],
                    ['value' => 'low', 'label' => '📉 Bas - Je me sens vidé(e)', 'emoji' => '📉'],
                    ['value' => 'medium', 'label' => '⚡ Moyen - Ni trop ni trop peu', 'emoji' => '⚡'],
                    ['value' => 'high', 'label' => '🚀 Élevé - Je suis en forme', 'emoji' => '🚀'],
                ]
            ],
            [
                'id' => 'time',
                'question' => 'Combien de temps avez-vous disponible ?',
                'options' => [
                    ['value' => '5-10', 'label' => '⚡ 5-10 minutes', 'emoji' => '⚡'],
                    ['value' => '15-20', 'label' => '⏰ 15-20 minutes', 'emoji' => '⏰'],
                    ['value' => '30+', 'label' => '🕐 30 minutes ou plus', 'emoji' => '🕐'],
                ]
            ],
            [
                'id' => 'preference',
                'question' => 'Que préférez-vous en ce moment ?',
                'options' => [
                    ['value' => 'calm', 'label' => '🧘 Activités calmes et méditatives', 'emoji' => '🧘'],
                    ['value' => 'active', 'label' => '🏃 Activités dynamiques et physiques', 'emoji' => '🏃'],
                    ['value' => 'creative', 'label' => '✍️ Activités créatives et réflexives', 'emoji' => '✍️'],
                ]
            ],
            [
                'id' => 'goal',
                'question' => 'Quel est votre objectif principal ?',
                'options' => [
                    ['value' => 'reduce_stress', 'label' => '😌 Réduire le stress et l\'anxiété', 'emoji' => '😌'],
                    ['value' => 'gain_energy', 'label' => '💪 Gagner de l\'énergie', 'emoji' => '💪'],
                    ['value' => 'better_sleep', 'label' => '😴 Mieux dormir ce soir', 'emoji' => '😴'],
                    ['value' => 'focus', 'label' => '🎯 Améliorer ma concentration', 'emoji' => '🎯'],
                ]
            ],
        ];
    }

    /**
     * Analyse les réponses avec l'IA et recommande des activités
     */
    public function analyzeAndRecommend(array $answers): array
    {
        // Récupérer toutes les activités disponibles
        $activities = $this->activiteRepo->createQueryBuilder('a')
            ->where('a.estActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        if (empty($activities)) {
            return [
                'recommendations' => [],
                'explanation' => 'Aucune activité disponible pour le moment.',
            ];
        }

        // Préparer les activités pour l'IA
        $activitiesData = array_map(function (ActiviteBienEtre $activity) {
            return [
                'id' => $activity->getId(),
                'nom' => $activity->getNomActivite(),
                'type' => $activity->getTypeActivite(),
                'duree' => $activity->getDureeSuggeree(),
                'categorie' => $activity->getCategorie(),
                'niveau' => $activity->getNiveauDifficulte()?->value,
                'objectif' => $activity->getObjectifEmotionnel(),
                'description' => $activity->getDescription(),
            ];
        }, $activities);

        // Construire le prompt pour l'IA
        $prompt = $this->buildPrompt($answers, $activitiesData);

        // Appeler l'API Gemini
        $aiResponse = $this->callGeminiAPI($prompt);

        // Parser la réponse de l'IA
        return $this->parseAIResponse($aiResponse, $activities);
    }

    /**
     * Construit le prompt pour l'IA
     */
    private function buildPrompt(array $answers, array $activities): string
    {
        $answersText = "Réponses de l'utilisateur :\n";
        foreach ($answers as $key => $value) {
            $answersText .= "- $key: $value\n";
        }

        $activitiesText = "Activités disponibles :\n" . json_encode($activities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Tu es un expert en bien-être et santé mentale. Un utilisateur a répondu à un quiz sur son état actuel.

$answersText

Voici les activités de bien-être disponibles :
$activitiesText

Ta mission :
1. Analyse les réponses de l'utilisateur
2. Recommande exactement 2-3 activités parmi celles disponibles qui correspondent le mieux à ses besoins
3. Pour chaque activité recommandée, explique POURQUOI elle est adaptée à sa situation

Réponds UNIQUEMENT au format JSON suivant (sans markdown, sans texte avant ou après) :
{
  "recommendations": [
    {
      "activity_id": 1,
      "reason": "Explication personnalisée de pourquoi cette activité est parfaite pour lui",
      "priority": 1
    }
  ],
  "global_message": "Message d'encouragement personnalisé basé sur ses réponses"
}

IMPORTANT : Réponds UNIQUEMENT avec le JSON, rien d'autre.
PROMPT;
    }

    /**
     * Appelle l'API Gemini
     */
    private function callGeminiAPI(string $prompt): string
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'query' => [
                    'key' => $this->geminiApiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1024,
                    ],
                ],
            ]);

            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            return '{"recommendations": [], "global_message": "Erreur lors de l\'analyse."}';
        } catch (\Exception $e) {
            return '{"recommendations": [], "global_message": "Erreur lors de l\'analyse: ' . $e->getMessage() . '"}';
        }
    }

    /**
     * Parse la réponse de l'IA
     */
    private function parseAIResponse(string $aiResponse, array $activities): array
    {
        // Nettoyer la réponse (enlever les markdown si présents)
        $aiResponse = trim($aiResponse);
        $aiResponse = preg_replace('/```json\s*|\s*```/', '', $aiResponse);

        try {
            $parsed = json_decode($aiResponse, true);

            if (!$parsed || !isset($parsed['recommendations'])) {
                throw new \Exception('Format JSON invalide');
            }

            // Mapper les IDs aux activités réelles
            $recommendations = [];
            foreach ($parsed['recommendations'] as $rec) {
                $activityId = $rec['activity_id'];
                $activity = array_filter($activities, fn($a) => $a->getId() === $activityId);
                
                if (!empty($activity)) {
                    $recommendations[] = [
                        'activity' => array_values($activity)[0],
                        'reason' => $rec['reason'],
                        'priority' => $rec['priority'] ?? 1,
                    ];
                }
            }

            // Trier par priorité
            usort($recommendations, fn($a, $b) => $a['priority'] <=> $b['priority']);

            return [
                'recommendations' => $recommendations,
                'global_message' => $parsed['global_message'] ?? 'Voici vos recommandations personnalisées.',
            ];
        } catch (\Exception $e) {
            // Fallback : recommandations basiques
            return $this->getFallbackRecommendations($activities);
        }
    }

    /**
     * Recommandations de secours si l'IA échoue
     */
    private function getFallbackRecommendations(array $activities): array
    {
        $randomActivities = array_slice($activities, 0, 2);

        return [
            'recommendations' => array_map(function ($activity) {
                return [
                    'activity' => $activity,
                    'reason' => 'Activité recommandée pour votre bien-être général.',
                    'priority' => 1,
                ];
            }, $randomActivities),
            'global_message' => 'Voici quelques activités qui pourraient vous aider.',
        ];
    }
}