<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WelcomeEmailService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private MailerInterface     $mailer,
        private HttpClientInterface $httpClient,
        private LoggerInterface     $logger,
        private string              $groqApiKey,
        private string              $senderEmail = 'nawellaouini210@gmail.com'
    ) {}

    public function sendWelcomeEmail(Utilisateur $user, string $role): void
    {
        try {
            $content = $this->generateWelcomeContent($user, $role);

            $this->logger->info('📧 Tentative envoi email', [
                'to'      => $user->getEmail(),
                'subject' => $content['subject'],
            ]);

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, 'FeelSafe'))
                ->to(new Address($user->getEmail(), $user->getPrenom() . ' ' . $user->getNom()))
                ->subject($content['subject'])
                ->htmlTemplate('emails/welcome.html.twig')
                ->context([
                    'user'        => $user,
                    'role'        => $role,
                    'roleLabel'   => $content['roleLabel'],
                    'greeting'    => $content['greeting'],
                    'mainMessage' => $content['mainMessage'],
                    'tips'        => $content['tips'],
                    'closing'     => $content['closing'],
                    'subject'     => $content['subject'],
                ]);

            $this->mailer->send($email);
            $this->logger->info('✅ Email envoyé avec succès', ['email' => $user->getEmail()]);

        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            $this->logger->error('❌ Erreur SMTP', [
                'message' => $e->getMessage(),
                'email'   => $user->getEmail(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur générale email', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }

    private function generateWelcomeContent(Utilisateur $user, string $role): array
    {
        $roleLabel = match ($role) {
            'ROLE_ADMIN'         => 'Administrateur',
            'ROLE_PROFESSIONNEL' => 'Professionnel de santé',
            default              => 'Client',
        };

        $prompt = $this->buildPrompt($user->getPrenom(), $user->getNom(), $roleLabel);

        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama3-8b-8192',
                    'temperature' => 0.7,
                    'max_tokens'  => 600,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un assistant pour la plateforme FeelSafe dédiée à la santé mentale. Tu rédiges des emails de bienvenue chaleureux, professionnels et bienveillants en français. Tu réponds UNIQUEMENT en JSON valide, sans markdown, sans backticks.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            $raw  = $data['choices'][0]['message']['content'] ?? '';

            $raw = preg_replace('/```json\s*/i', '', $raw);
            $raw = preg_replace('/```\s*/i', '', $raw);
            $raw = trim($raw);

            $parsed = json_decode($raw, true);

            if (is_array($parsed) && isset($parsed['subject'])) {
                $parsed['roleLabel'] = $roleLabel;
                return $parsed;
            }

        } catch (\Exception $e) {
            $this->logger->warning('⚠️ GROQ indisponible, contenu par défaut', [
                'error' => $e->getMessage()
            ]);
        }

        return $this->getDefaultContent($user->getPrenom(), $roleLabel);
    }

    private function buildPrompt(string $prenom, string $nom, string $roleLabel): string
    {
        return <<<PROMPT
Génère un email de bienvenue pour {$prenom} {$nom} qui vient de créer un compte sur FeelSafe (plateforme de santé mentale) avec le rôle : {$roleLabel}.

Réponds UNIQUEMENT avec ce JSON (pas de markdown, pas de backticks) :
{
  "subject": "Objet de l'email (court, chaleureux)",
  "greeting": "Formule d'accueil personnalisée avec le prénom",
  "mainMessage": "Paragraphe principal de bienvenue adapté au rôle (2-3 phrases)",
  "tips": ["conseil 1 spécifique au rôle", "conseil 2", "conseil 3"],
  "closing": "Formule de clôture chaleureuse"
}
PROMPT;
    }

    private function getDefaultContent(string $prenom, string $roleLabel): array
    {
        $contentByRole = [
            'Administrateur' => [
                'subject'     => '🛡️ Bienvenue sur FeelSafe — Accès Administrateur activé',
                'greeting'    => "Bonjour {$prenom},",
                'mainMessage' => "Votre compte administrateur FeelSafe a été créé avec succès. Vous disposez désormais d'un accès complet à la gestion de la plateforme. Votre rôle est essentiel pour garantir la sécurité et le bon fonctionnement de l'espace.",
                'tips'        => [
                    "Consultez le tableau de bord pour surveiller l'activité des utilisateurs",
                    "Vérifiez régulièrement les signalements et les urgences",
                    "Assurez-vous que les paramètres de sécurité sont à jour",
                ],
                'closing' => "L'équipe FeelSafe vous souhaite la bienvenue dans cette mission importante.",
            ],
            'Professionnel de santé' => [
                'subject'     => '💼 Bienvenue sur FeelSafe — Espace Professionnel',
                'greeting'    => "Bonjour Dr. {$prenom},",
                'mainMessage' => "Bienvenue sur FeelSafe ! Votre profil professionnel est maintenant actif. Vous pouvez accéder aux dossiers partagés par vos patients et utiliser nos outils d'accompagnement clinique.",
                'tips'        => [
                    "Complétez votre profil professionnel pour être trouvé par vos patients",
                    "Découvrez les journaux émotionnels partagés par vos patients",
                    "Utilisez le module de suivi pour mieux accompagner vos consultations",
                ],
                'closing' => "Merci de rejoindre FeelSafe et de contribuer au bien-être mental de notre communauté.",
            ],
        ];

        $defaultContent = [
            'subject'     => '💚 Bienvenue sur FeelSafe — Votre espace bien-être',
            'greeting'    => "Bonjour {$prenom} !",
            'mainMessage' => "Votre compte FeelSafe est prêt ! Nous sommes ravis de vous accueillir dans votre espace personnel dédié à la santé mentale. Ici, vous pouvez exprimer vos émotions, suivre votre bien-être et trouver le soutien dont vous avez besoin.",
            'tips'        => [
                "Commencez par créer votre premier journal émotionnel",
                "Explorez les activités de bien-être disponibles",
                "N'hésitez pas à consulter un professionnel si vous en ressentez le besoin",
            ],
            'closing' => "Prenez soin de vous. L'équipe FeelSafe est là pour vous accompagner. 💚",
        ];

        $content             = $contentByRole[$roleLabel] ?? $defaultContent;
        $content['roleLabel'] = $roleLabel;
        return $content;
    }
}