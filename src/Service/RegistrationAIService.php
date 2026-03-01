<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class RegistrationAIService
{
    /** @phpstan-ignore-next-line */
    private const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        /** @phpstan-ignore-next-line */
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        /** @phpstan-ignore-next-line */
        private string $openrouterApiKey
    ) { 
        $this->logger->debug('RegistrationAIService initialized');
    }

    /**
     * ✅ Valider un nom/prénom avec règles strictes LOCALES (pas d'IA)
     */
    public function validateName(string $name, string $type = 'prénom'): array
    {
        // 1. Vérifier la longueur
        if (strlen($name) < 2) {
            return [
                'valid' => false,
                'reason' => "Le {$type} doit contenir au moins 2 caractères."
            ];
        }

        if (strlen($name) > 50) {
            return [
                'valid' => false,
                'reason' => "Le {$type} est trop long (maximum 50 caractères)."
            ];
        }

        // 2. Pas de chiffres
        if (preg_match('/\d/', $name)) {
            return [
                'valid' => false,
                'reason' => "Le {$type} ne peut pas contenir de chiffres."
            ];
        }

        // 3. Seulement des lettres (avec accents) et tirets/espaces
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/', $name)) {
            return [
                'valid' => false,
                'reason' => "Le {$type} contient des caractères invalides."
            ];
        }

        // 4. ✅ NOUVEAU: Détecter les patterns répétitifs (aaaaa, bbbb, etc.)
        if (preg_match('/(.)\1{3,}/', $name)) {
            return [
                'valid' => false,
                'reason' => "Le {$type} semble invalide (caractères répétés)."
            ];
        }

        // 5. ✅ NOUVEAU: Vérifier que ce n'est pas juste des consonnes aléatoires
        $voyelles = preg_match_all('/[aeiouyAEIOUYàéèêëïîôùûüÿ]/i', $name);
        $consonnes = preg_match_all('/[bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ]/i', $name);

        // Un nom réel doit avoir au moins 1 voyelle
        if ($voyelles === 0 && strlen($name) > 3) {
            return [
                'valid' => false,
                'reason' => "Le {$type} ne semble pas être un vrai nom."
            ];
        }

        // 6. ✅ NOUVEAU: Liste noire de noms fake communs
        $blacklist = [
            'aaa',
            'aaaa',
            'aaaaa',
            'aaaaaa',
            'aaaaaaa',
            'bbb',
            'bbbb',
            'bbbbb',
            'ccc',
            'cccc',
            'ccccc',
            'test',
            'testing',
            'fake',
            'random',
            'qwerty',
            'azerty',
            'asdfgh',
            'zxcvbn',
            'jhgfds',
            'fdsa',
            'gfds',
        ];

        if (in_array(strtolower($name), $blacklist)) {
            return [
                'valid' => false,
                'reason' => "Ce {$type} n'est pas valide. Veuillez utiliser votre vrai {$type}."
            ];
        }

        // ✅ Si toutes les validations passent
        return [
            'valid' => true,
            'reason' => ''
        ];
    }

    /**
     * Détecter les emails temporaires/jetables
     */
    public function validateEmail(string $email): array
    {
        // Vérifier le format de base
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'reason' => 'Format d\'email invalide.'
            ];
        }

        // Liste étendue des domaines d'emails temporaires
        $tempDomains = [
            'tempmail.com',
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'throwaway.email',
            'fakeinbox.com',
            'temp-mail.org',
            'getnada.com',
            'trashmail.com',
            'yopmail.com',
            'sharklasers.com',
            'guerrillamail.info',
            'pokemail.net',
            'spam4.me',
            'maildrop.cc',
            'tempinbox.com',
            'mintemail.com',
            'emailondeck.com',
            'jetable.org',
            'trashmail.ws',
            'mailnesia.com',
            'throwawaymail.com',
            'mytemp.email',
            'tempail.com',
        ];

        $domain = substr(strrchr($email, "@"), 1);

        if (in_array(strtolower($domain), $tempDomains)) {
            return [
                'valid' => false,
                'reason' => 'Email temporaire détecté. Veuillez utiliser un email permanent (Gmail, Outlook, Yahoo...).'
            ];
        }

        // ✅ Vérifier que ce n'est pas un domaine suspect
        $suspiciousDomains = ['test.com', 'fake.com', 'example.com', 'localhost'];
        if (in_array(strtolower($domain), $suspiciousDomains)) {
            return [
                'valid' => false,
                'reason' => 'Ce domaine d\'email n\'est pas autorisé.'
            ];
        }

        return [
            'valid' => true,
            'reason' => ''
        ];
    }

    /**
     * Analyser la force d'un mot de passe
     */
    public function analyzePasswordStrength(string $password): array
    {
        $score = 0;
        $suggestions = [];

        // Longueur
        if (strlen($password) >= 8)
            $score += 25;
        if (strlen($password) >= 12)
            $score += 10;
        if (strlen($password) >= 16)
            $score += 10;

        // Minuscules
        if (preg_match('/[a-z]/', $password)) {
            $score += 15;
        } else {
            $suggestions[] = 'Ajoutez des lettres minuscules';
        }

        // Majuscules
        if (preg_match('/[A-Z]/', $password)) {
            $score += 15;
        } else {
            $suggestions[] = 'Ajoutez des lettres majuscules';
        }

        // Chiffres
        if (preg_match('/\d/', $password)) {
            $score += 15;
        } else {
            $suggestions[] = 'Ajoutez des chiffres';
        }

        // Caractères spéciaux
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 20;
        } else {
            $suggestions[] = 'Ajoutez des caractères spéciaux (!@#$%^&*)';
        }

        // Pénalités pour patterns faibles
        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 10; // Caractères répétés
        }
        if (preg_match('/123|234|345|456|567|678|789|abc|bcd|qwe|wer|ert/i', $password)) {
            $score -= 15; // Séquences communes
        }

        $score = max(0, min(100, $score)); // Limiter entre 0 et 100

        // Déterminer le niveau
        if ($score < 40) {
            $level = 'Très faible';
            $color = '#dc2626'; // red-600
        } elseif ($score < 60) {
            $level = 'Faible';
            $color = '#f97316'; // orange-500
        } elseif ($score < 80) {
            $level = 'Moyen';
            $color = '#eab308'; // yellow-500
        } elseif ($score < 90) {
            $level = 'Fort';
            $color = '#22c55e'; // green-500
        } else {
            $level = 'Très fort';
            $color = '#16a34a'; // green-600
        }

        return [
            'score' => $score,
            'level' => $level,
            'color' => $color,
            'suggestions' => $suggestions
        ];
    }
}