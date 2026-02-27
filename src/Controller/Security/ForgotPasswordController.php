<?php

namespace App\Controller\Security;

use App\Entity\Utilisateur;
use App\Entity\PasswordResetToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Page de saisie de l'email
     */
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('auth/forgot-password/index.html.twig');
    }

    /**
     * Traitement : créer le token et ENVOYER L'EMAIL
     */
    #[Route('/forgot-password/submit', name: 'app_forgot_password_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer): Response
    {
        $email = trim($request->request->get('email', ''));

        // Validation de l'email
        if (empty($email)) {
            $this->addFlash('error', 'Le champ "Adresse email" est obligatoire.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Veuillez entrer une adresse email valide.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Chercher l'utilisateur
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        // Si l'utilisateur n'existe pas
        if ($user === null) {
            $this->addFlash('error', 'Aucun compte n\'existe avec cet email.');
            return $this->redirectToRoute('app_forgot_password');
        }

        try {
            // Supprimer tous les anciens tokens
            $oldTokens = $this->entityManager->getRepository(PasswordResetToken::class)
                ->findBy(['user' => $user]);
            
            foreach ($oldTokens as $oldToken) {
                $this->entityManager->remove($oldToken);
            }
            $this->entityManager->flush();

            // Générer un nouveau token
            $token = bin2hex(random_bytes(32));

            // Créer le token en base de données
            $resetToken = new PasswordResetToken();
            $resetToken->setUser($user);
            $resetToken->setToken($token);

            $this->entityManager->persist($resetToken);
            $this->entityManager->flush();

            // Générer l'URL complète de réinitialisation
            $resetUrl = $this->generateUrl('app_reset_password', 
                ['token' => $token], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Créer et envoyer l'email
            $emailMessage = (new TemplatedEmail())
                ->from(new Address('noreply@feelsafe.com', 'FeelSafe'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject('Réinitialisation de votre mot de passe - FeelSafe')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->context([
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                    'token' => $token,
                ]);

            $mailer->send($emailMessage);

            // Log de succès
            $this->logger->info('✅ Email de réinitialisation envoyé', [
                'email' => $email,
                'token' => substr($token, 0, 10) . '...'
            ]);

            // Rediriger vers la page de confirmation
            $this->addFlash('success', 'Un email de réinitialisation a été envoyé à votre adresse email.');
            return $this->redirectToRoute('app_forgot_password_sent');

        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur lors de l\'envoi de l\'email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email: ' . $e->getMessage());
            return $this->redirectToRoute('app_forgot_password');
        }
    }

    /**
     * Page de confirmation après envoi d'email
     */
    #[Route('/forgot-password/sent', name: 'app_forgot_password_sent')]
    public function sent(): Response
    {
        return $this->render('auth/forgot-password/email-sent.html.twig');
    }

    /**
     * Page de réinitialisation du mot de passe
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(string $token, Request $request): Response
    {
        // 🔍 LOG: Afficher le token reçu
        $this->logger->info('🔍 Tentative de réinitialisation avec token', ['token' => $token]);

        // Chercher le token dans la base de données
        $resetToken = $this->entityManager->getRepository(PasswordResetToken::class)
            ->findOneBy(['token' => $token]);

        // 🔍 LOG: Token trouvé ou non?
        if ($resetToken === null) {
            $this->logger->error('❌ Token introuvable dans la base de données');
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Vérifier si le token est valide (pas expiré et pas utilisé)
        if (!$resetToken->isValid()) {
            $this->logger->error('❌ Token expiré ou déjà utilisé', [
                'isUsed' => $resetToken->isUsed(),
                'expiresAt' => $resetToken->getExpiresAt()->format('Y-m-d H:i:s'),
                'now' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
            
            $this->addFlash('error', 'Ce lien de réinitialisation a expiré ou a déjà été utilisé.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $this->logger->info('✅ Token valide!');

        // Si le formulaire est soumis (POST)
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            // Validation du mot de passe
            if (empty($password) || empty($confirmPassword)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->render('auth/reset-password/index.html.twig', ['token' => $token]);
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->render('auth/reset-password/index.html.twig', ['token' => $token]);
            }

            if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.');
                return $this->render('auth/reset-password/index.html.twig', ['token' => $token]);
            }

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('auth/reset-password/index.html.twig', ['token' => $token]);
            }

            try {
                // Récupérer l'utilisateur
                $user = $resetToken->getUser();
                
                // Hasher le nouveau mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                
                // Mettre à jour le mot de passe
                $user->setPassword($hashedPassword);
                
                // Marquer le token comme utilisé
                $resetToken->setIsUsed(true);
                
                // Sauvegarder
                $this->entityManager->flush();

                $this->logger->info('✅ Mot de passe réinitialisé avec succès', [
                    'user' => $user->getEmail()
                ]);

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
                
            } catch (\Exception $e) {
                $this->logger->error('❌ Erreur lors de la réinitialisation', [
                    'error' => $e->getMessage()
                ]);
                
                $this->addFlash('error', 'Une erreur est survenue. Veuillez réessayer.');
                return $this->render('auth/reset-password/index.html.twig', ['token' => $token]);
            }
        }

        // Afficher le formulaire (GET)
        return $this->render('auth/reset-password/index.html.twig', [
            'token' => $token,
        ]);
    }
}