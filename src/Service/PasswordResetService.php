<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class PasswordResetService
{
    private const TOKEN_LENGTH = 32;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PasswordResetTokenRepository $tokenRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    /**
     * Génère et envoie un lien de réinitialisation
     * 
     * @return bool True si l'email a été envoyé (ne révèle pas si l'utilisateur existe)
     */
    public function requestPasswordReset(string $email, string $clientIp): bool
    {
        // Chercher l'utilisateur
        $user = $this->userRepository->findOneBy(['email' => $email]);

        // Protection timing attack : toujours prendre le même temps
        if ($user === null) {
            // Simuler le traitement pour éviter de révéler si l'email existe
            usleep(random_int(100000, 300000)); // 100-300ms
            $this->logger->info('Password reset requested for non-existent email', [
                'email' => $email,
                'ip' => $clientIp
            ]);
            return true;
        }

        // Vérifier que le compte est actif
        if ($user->getStatut() !== 'actif') {
            $this->logger->warning('Password reset attempted for inactive account', [
                'user_id' => $user->getId(),
                'email' => $email,
                'status' => $user->getStatut()
            ]);
            return true;
        }

        try {
            // Invalider tous les tokens précédents de cet utilisateur
            $this->tokenRepository->invalidateUserTokens($user);

            // Générer un token cryptographiquement sécurisé
            $token = $this->generateSecureToken();

            // Créer et sauvegarder le token
            $resetToken = new PasswordResetToken();
            $resetToken->setUser($user);
            $resetToken->setToken($token);

            $this->entityManager->persist($resetToken);
            $this->entityManager->flush();

            // Envoyer l'email
            $this->sendResetEmail($user, $token);

            $this->logger->info('Password reset email sent', [
                'user_id' => $user->getId(),
                'email' => $email
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return true;
        }
    }

    /**
     * Génère un token cryptographiquement sécurisé
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * Envoie l'email de réinitialisation
     */
    private function sendResetEmail(User $user, string $token): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@feelsafe.com', 'FeelSafe'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user' => $user,
                'token' => $token,
                'expirationTime' => '1 heure',
            ]);

        $this->mailer->send($email);
    }

    /**
     * Valide un token de réinitialisation
     */
    public function validateToken(string $token): ?PasswordResetToken
    {
        $resetToken = $this->tokenRepository->findValidToken($token);

        if ($resetToken === null) {
            $this->logger->warning('Invalid or expired password reset token used', [
                'token' => substr($token, 0, 8) . '...'
            ]);
            return null;
        }

        return $resetToken;
    }

    /**
     * Réinitialise le mot de passe avec un token valide
     */
    public function resetPassword(PasswordResetToken $resetToken, string $hashedPassword): bool
    {
        try {
            $user = $resetToken->getUser();
            
            // Mettre à jour le mot de passe
            $user->setPassword($hashedPassword);

            // Marquer le token comme utilisé
            $resetToken->setIsUsed(true);
            
            $this->entityManager->flush();

            $this->logger->info('Password successfully reset', [
                'user_id' => $user->getId()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to reset password', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Nettoie les tokens expirés (à appeler via une commande cron)
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->deleteExpiredTokens();
    }
}