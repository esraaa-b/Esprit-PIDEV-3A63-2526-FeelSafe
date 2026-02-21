<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiJournalController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $anthropicApiKey
    ) {}

    // ─────────────────────────────────────────────────────────────
    // 1. Analyse a single journal entry
    // POST /dashboard/journal/ai/analyse
    // Body: { entry_id: int, _token: string }
    // ─────────────────────────────────────────────────────────────
    #[Route('/dashboard/journal/ai/analyse', name: 'app_ai_journal_analyse', methods: ['POST'])]
    public function analyse(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$this->isCsrfTokenValid('ai_journal', $data['_token'] ?? '')) {
            return $this->json(['error' => 'Token invalide'], 403);
        }

        $entry = $em->getRepository(JournalEmotionnel::class)->find($data['entry_id'] ?? 0);

        if (!$entry || $entry->getUtilisateur() !== $this->getUser()) {
            return $this->json(['error' => 'Entrée introuvable'], 404);
        }

        $emotionLabel = $entry->getEmotion()->label();
        $content      = $entry->getContenu() ?? '';
        $date         = $entry->getDateCreation()->format('d/m/Y');

        if (empty(trim($content))) {
            return $this->json(['error' => 'Cette entrée ne contient pas de texte à analyser.'], 422);
        }

       $prompt = <<<PROMPT
Tu es un assistant bienveillant en bien-être émotionnel.
L'utilisateur a écrit le {$date}, émotion "{$emotionLabel}" :

"{$content}"

Réponds en français, style direct et chaleureux. Chaque section = 2 phrase complète maximum.

**Réflexion** : Ce que tu observes avec empathie (1 phrase).
**Pattern** : Ce que cela révèle, sans diagnostic (1 phrase).
**Conseil** : Une action simple pour aujourd'hui (2 phrase).

Si l'émotion est très intense, ajoute :
**Activité recommandée** : 1 activité concrète et immédiate (2 phrase).

Sois extrêmement concis. Termine toujours chaque phrase avant de t'arrêter.
PROMPT;
        $result = $this->callClaude($prompt, 500);
        if (isset($result['error'])) {
            return $this->json($result, 500);
        }

        return $this->json(['analysis' => $result['text']]);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. Weekly mood summary
    // POST /dashboard/journal/ai/weekly-summary
    // Body: { _token: string }
    // ─────────────────────────────────────────────────────────────
   #[Route('/dashboard/journal/ai/next-day-prediction', name: 'app_ai_journal_next_day_prediction', methods: ['POST'])]
public function nextDayPrediction(Request $request, EntityManagerInterface $em): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!$this->isCsrfTokenValid('ai_journal', $data['_token'] ?? '')) {
        return $this->json(['error' => 'Token invalide'], 403);
    }

    $user  = $this->getUser();
    $today = new \DateTime('today');

    $entries = $em->getRepository(JournalEmotionnel::class)
        ->createQueryBuilder('j')
        ->where('j.utilisateur = :user')
        ->andWhere('j.dateCreation >= :today')
        ->setParameter('user', $user)
        ->setParameter('today', $today)
        ->orderBy('j.dateCreation', 'ASC')
        ->getQuery()
        ->getResult();

    if (empty($entries)) {
        return $this->json(['error' => "Aucune entrée aujourd'hui pour générer une prédiction."], 422);
    }

    // Build today's digest
    $digest = '';
    foreach ($entries as $entry) {
        $time    = $entry->getDateCreation()->format('H:i');
        $emotion = $entry->getEmotion()->label();
        $text    = $entry->getContenu() ? '"' . mb_substr($entry->getContenu(), 0, 150) . '"' : '(aucun texte)';
        $digest .= "- {$time} [{$emotion}] : {$text}\n";
    }

    $count    = count($entries);
    $tomorrow = (new \DateTime('+1 day'))->format('l d/m');

    $prompt = <<<PROMPT
Tu es un assistant bienveillant spécialisé en bien-être émotionnel et en psychologie positive.
Voici les {$count} entrées de journal de l'utilisateur pour aujourd'hui :

{$digest}

En te basant sur ces émotions et leur évolution au fil de la journée, génère en français une prédiction douce et encourageante pour demain ({$tomorrow}), avec exactement ces 3 sections (1 phrases chacune) :

1. **Ton énergie pour demain** : Prédit l'état émotionnel probable de demain en tenant compte de la trajectoire d'aujourd'hui.
2. **À surveiller** : Un point d'attention ou un déclencheur potentiel à garder en tête, formulé positivement.
3. **Une intention pour demain** : Un conseil concret et bienveillant pour aborder la journée de façon sereine.

Important : ce sont des prédictions bienveillantes, pas des certitudes. Utilise un ton doux ("il est probable que", "tu pourrais ressentir"...). Parle directement à l'utilisateur (tu).
PROMPT;

    $result = $this->callClaude($prompt, 200);
    if (isset($result['error'])) {
        return $this->json($result, 500);
    }

    return $this->json([
        'prediction'   => $result['text'],
        'entry_count'  => $count,
        'based_on'     => (new \DateTime())->format('d/m/Y'),
        'predicts_for' => (new \DateTime('+1 day'))->format('d/m/Y'),
    ]);
}

    // ─────────────────────────────────────────────────────────────
    // 3. Daily AI-generated affirmation
    // POST /dashboard/journal/ai/affirmation
    // Body: { _token: string }
    // ─────────────────────────────────────────────────────────────
    #[Route('/dashboard/journal/ai/affirmation', name: 'app_ai_journal_affirmation', methods: ['POST'])]
    public function affirmation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$this->isCsrfTokenValid('ai_journal', $data['_token'] ?? '')) {
            return $this->json(['error' => 'Token invalide'], 403);
        }

        $user = $this->getUser();

        // Get the last 5 entries for context
        $recent = $em->getRepository(JournalEmotionnel::class)
            ->findBy(['utilisateur' => $user], ['dateCreation' => 'DESC'], 5);

        $context = '';
        if (!empty($recent)) {
            $emotions = array_map(fn($e) => $e->getEmotion()->label(), $recent);
            $context  = 'Les émotions récentes de l\'utilisateur : ' . implode(', ', $emotions) . '.';
        }

        $today = (new \DateTime())->format('l d F Y');

        $prompt = <<<PROMPT
Tu es un guide bienveillant en bien-être émotionnel.
{$context}
Nous sommes le {$today}.

Génère en français UNE affirmation positive personnalisée et authentique (3 phrases maximum).
Elle doit :
- Être ancrée dans le moment présent
- Être adaptée aux émotions récentes de l'utilisateur (si disponibles)
- Sonner vraie et humaine, pas générique
- Se terminer par une micro-action concrète pour aujourd'hui

Ne commence PAS par "Je suis" — sois créatif dans la formulation.
PROMPT;

        $result = $this->callClaude($prompt, 200);
        if (isset($result['error'])) {
            return $this->json($result, 500);
        }

        return $this->json([
            'affirmation' => $result['text'],
            'date'        => (new \DateTime())->format('d/m/Y'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Simple chat endpoint for compassionate conversation
    // POST /dashboard/journal/ai/chat
    // Body: { _token: string, message: string }
    // ─────────────────────────────────────────────────────────────
    #[Route('/dashboard/journal/ai/chat', name: 'app_ai_journal_chat', methods: ['POST'])]
    public function chat(Request $request, EntityManagerInterface $em, ChatMessageRepository $chatRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$this->isCsrfTokenValid('ai_journal', $data['_token'] ?? '')) {
            return $this->json(['error' => 'Token invalide'], 403);
        }

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '') {
            return $this->json(['error' => 'Message vide'], 422);
        }

        $user = $this->getUser();

        // Determine conversation id (reuse provided or create new)
        $conversationId = $data['conversationId'] ?? null;
        if (empty($conversationId)) {
            $conversationId = bin2hex(random_bytes(8));
        }

        // Persist user message
        $userMsg = new \App\Entity\ChatMessage();
        $userMsg->setConversationId($conversationId)
            ->setRole('user')
            ->setContent($message)
            ->setUtilisateur($user);
        $chatRepo->save($userMsg, true);

        // Provide lightweight context from recent entries
        $recent = $em->getRepository(JournalEmotionnel::class)
            ->findBy(['utilisateur' => $user], ['dateCreation' => 'DESC'], 5);

        $context = '';
        if (!empty($recent)) {
            $lines = [];
            foreach ($recent as $e) {
                $txt = $e->getContenu() ? mb_substr($e->getContenu(), 0, 200) : '(aucun texte)';
                $lines[] = $e->getDateCreation()->format('d/m') . ' [' . $e->getEmotion()->label() . '] ' . $txt;
            }
            $context = "Entrées récentes:\n- " . implode("\n- ", $lines) . "\n\n";
        }

        $prompt = <<<PROMPT
Tu es un assistant empathique et sécurisant pour la santé mentale, parlant français.
Fais une réponse concise et bienveillante destinée à un utilisateur qui vient de t'écrire :
"{$message}"

Contexte utile :
{$context}

Objectifs :
- Reconnaître les émotions de l'utilisateur avec empathie.
- Offrir confort, validation et une suggestion pratique et sans diagnostic.
- Si le message exprime détresse sévère (pensées suicidaires, automutilation), donne un message court encourageant à contacter des services d'urgence ou une ligne d'aide locale, et évite tout ton clinique.

Réponds en français, 3 à 6 phrases maximum, chaleureux et humain.
PROMPT;

        $result = $this->callClaude($prompt, 700);
        if (isset($result['error'])) {
            return $this->json($result, 500);
        }

        // Persist assistant reply
        $replyText = $result['text'] ?? '';
        $assistantMsg = new \App\Entity\ChatMessage();
        $assistantMsg->setConversationId($conversationId)
            ->setRole('assistant')
            ->setContent($replyText)
            ->setUtilisateur($user);
        $chatRepo->save($assistantMsg, true);

        return $this->json(['reply' => $replyText, 'conversationId' => $conversationId]);
    }

    // (Removed viewConversation route - conversations are viewed inside the drawer)

    #[Route('/dashboard/journal/ai/conversations', name: 'app_ai_journal_conversations', methods: ['GET'])]
    public function listConversations(EntityManagerInterface $em, ChatMessageRepository $chatRepo): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $qb = $chatRepo->createQueryBuilder('c')
            ->select('c.conversationId')
            ->where('c.utilisateur = :user')
            ->setParameter('user', $user)
            ->groupBy('c.conversationId')
            ->orderBy('MAX(c.createdAt)', 'DESC');

        $rows = $qb->getQuery()->getResult();

        $conversations = [];
        foreach ($rows as $r) {
            $convId = is_array($r) ? ($r['conversationId'] ?? reset($r)) : $r;
            $last = $chatRepo->findOneBy(['conversationId' => $convId, 'utilisateur' => $user], ['createdAt' => 'DESC']);
            $conversations[] = [
                'conversationId' => $convId,
                'lastMessage' => $last ? [
                    'role' => $last->getRole(),
                    'content' => mb_substr($last->getContent(), 0, 500),
                    'createdAt' => $last->getCreatedAt()->format(DATE_ATOM),
                ] : null,
            ];
        }

        return $this->json(['conversations' => $conversations]);
    }

    #[Route('/dashboard/journal/ai/conversation/{conversationId}/messages', name: 'app_ai_journal_conversation_messages', methods: ['GET'])]
    public function conversationMessages(string $conversationId, ChatMessageRepository $chatRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $messages = $chatRepo->createQueryBuilder('c')
            ->where('c.conversationId = :conv')
            ->andWhere('c.utilisateur = :user')
            ->setParameter('conv', $conversationId)
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $out = array_map(function($m) {
            return [
                'role' => $m->getRole(),
                'content' => $m->getContent(),
                'createdAt' => $m->getCreatedAt()->format(DATE_ATOM),
            ];
        }, $messages);

        return $this->json(['messages' => $out]);
    }

    // ─────────────────────────────────────────────────────────────
    // Private: call Claude API
    // ─────────────────────────────────────────────────────────────
 private function callClaude(string $prompt, int $maxTokens = 400): array
{
    try {
        $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->anthropicApiKey, // put your Groq key in the same env var
                'content-type'  => 'application/json',
            ],
            'json' => [
                'model'      => 'llama-3.3-70b-versatile', // free, very capable
                'max_tokens' => $maxTokens,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $body = $response->toArray(false);

        if (isset($body['error'])) {
            return ['error' => 'Groq: ' . ($body['error']['message'] ?? json_encode($body['error']))];
        }

        return ['text' => $body['choices'][0]['message']['content'] ?? ''];
    } catch (\Throwable $e) {
        return ['error' => 'Erreur API : ' . $e->getMessage()];
    }
}
}