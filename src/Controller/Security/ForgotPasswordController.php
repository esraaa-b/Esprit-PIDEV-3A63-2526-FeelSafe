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
     * Traitement : créer le token et afficher la page de confirmation
     */
    #[Route('/forgot-password/submit', name: 'app_forgot_password_submit', methods: ['POST'])]
    public function submit(Request $request): Response
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

        // Chercher l'utilisateur dans la table 'utilisateur'
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        // Si l'utilisateur n'existe pas
        if ($user === null) {
            $this->addFlash('error', 'Aucun compte n\'existe avec cet email.');
            return $this->redirectToRoute('app_forgot_password');
        }

        try {
            // Supprimer tous les anciens tokens de cet utilisateur
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

            // Log de succès
            $this->logger->info('Token de réinitialisation créé', [
                'email' => $email,
                'token' => substr($token, 0, 10) . '...'
            ]);

            // ✅ Afficher la page de confirmation avec le lien
            return $this->render('auth/forgot-password/confirmation.html.twig', [
                'token' => $token,
                'email' => $email,
            ]);

        } catch (\Exception $e) {
            // Log de l'erreur avec détails
            $this->logger->error('Erreur lors de la création du token de réinitialisation', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('app_forgot_password');
        }
    }

    /**
     * Page de réinitialisation du mot de passe
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(string $token, Request $request): Response
    {
        // Chercher le token dans la base de données
        $resetToken = $this->entityManager->getRepository(PasswordResetToken::class)
            ->createQueryBuilder('prt')
            ->where('prt.token = :token')
            ->andWhere('prt.expiresAt > :now')
            ->andWhere('prt.isUsed = :isUsed')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->setParameter('isUsed', false)
            ->getQuery()
            ->getOneOrNullResult();

        // Vérifier si le token existe et est valide
        if ($resetToken === null) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Si le formulaire est soumis (POST)
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            // Validation du mot de passe
            if (empty($password)) {
                $this->addFlash('error', 'Le champ "Nouveau mot de passe" est obligatoire.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
            }

            if (empty($confirmPassword)) {
                $this->addFlash('error', 'Le champ "Confirmer le mot de passe" est obligatoire.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
            }

            // Vérifier les critères de sécurité
            if (!preg_match('/[a-z]/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une lettre minuscule.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
            }

            if (!preg_match('/[A-Z]/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une lettre majuscule.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
            }

            if (!preg_match('/\d/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins un chiffre.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
            }

            // Vérifier que les mots de passe correspondent
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
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

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la réinitialisation du mot de passe', [
                    'error' => $e->getMessage()
                ]);
                
                $this->addFlash('error', 'Une erreur est survenue lors de la réinitialisation. Veuillez réessayer.');
                return $this->render('auth/reset-password/index.html.twig', [
                    'token' => $token,
                ]);
            }
        }

        // Afficher le formulaire (GET)
        return $this->render('auth/reset-password/index.html.twig', [
            'token' => $token,
        ]);
    }
}