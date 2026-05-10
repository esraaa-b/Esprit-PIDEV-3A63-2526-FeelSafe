<?php

namespace App\Controller\Security;

use App\Entity\Utilisateur;
use App\Entity\ConfidentialiteUtilisateur;
use App\Form\RegistrationFormType;
use App\Service\RegistrationAIService;
use App\Service\WelcomeEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class RegisterController extends AbstractController
{
    public function __construct(
        private RegistrationAIService $aiService,
        private WelcomeEmailService   $welcomeEmailService
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        $user = new Utilisateur();

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'attr' => ['novalidate' => 'novalidate']
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $logger->info('🚀 Début inscription', [
                    'email'  => $user->getEmail(),
                    'prenom' => $user->getPrenom(),
                    'nom'    => $user->getNom()
                ]);

                // ✅ Validation IA : Prénom
                try {
                    $prenomValidation = $this->aiService->validateName($user->getPrenom(), 'prénom');
                    if (!$prenomValidation['valid']) {
                        $this->addFlash('error', 'Prénom invalide : ' . $prenomValidation['reason']);
                        return $this->redirectToRoute('app_register');
                    }
                } catch (\Exception $e) {
                    $logger->warning('⚠️ Validation IA prénom ignorée', ['error' => $e->getMessage()]);
                }

                // ✅ Validation IA : Nom
                try {
                    $nomValidation = $this->aiService->validateName($user->getNom(), 'nom');
                    if (!$nomValidation['valid']) {
                        $this->addFlash('error', 'Nom invalide : ' . $nomValidation['reason']);
                        return $this->redirectToRoute('app_register');
                    }
                } catch (\Exception $e) {
                    $logger->warning('⚠️ Validation IA nom ignorée', ['error' => $e->getMessage()]);
                }

                // ✅ Validation IA : Email
                try {
                    $emailValidation = $this->aiService->validateEmail($user->getEmail());
                    if (!$emailValidation['valid']) {
                        $this->addFlash('error', $emailValidation['reason']);
                        return $this->redirectToRoute('app_register');
                    }
                } catch (\Exception $e) {
                    $logger->warning('⚠️ Validation IA email ignorée', ['error' => $e->getMessage()]);
                }

                // Rôle choisi
                $roleChoisi = $form->get('role')->getData();
                $logger->info('📋 Rôle choisi : ' . $roleChoisi);

                // Hash mot de passe
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setMotDePasse($hashedPassword);
                $user->setRoles([$roleChoisi]);
                $user->setStatut('actif');

                // Confidentialité
                $confidentialite = new ConfidentialiteUtilisateur();
                $confidentialite->setUtilisateur($user);
                $confidentialite->setVisibiliteProfil($form->get('visibiliteProfil')->getData());
                $confidentialite->setPartageDonnees($form->get('partageDonnees')->getData() ?? false);
                $confidentialite->setNotificationsEmail($form->get('notificationsEmail')->getData() ?? true);
                $user->setConfidentialite($confidentialite);

                // Persister
                $logger->info('💾 Sauvegarde en base de données...');
                $entityManager->persist($user);
                $entityManager->flush();
                $logger->info('✅ Inscription réussie !', ['user_id' => $user->getId()]);

                // ✉️ Envoyer l'email de bienvenue généré par GROQ
                $this->welcomeEmailService->sendWelcomeEmail($user, $roleChoisi);

                $this->addFlash('success', '✅ Compte créé avec succès ! Un email de bienvenue vous a été envoyé.');
                return $this->redirectToRoute('app_login');

            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $logger->error('❌ Email déjà utilisé', ['email' => $user->getEmail()]);
                $this->addFlash('error', '📧 Cet email est déjà utilisé.');

            } catch (\Doctrine\DBAL\Exception $e) {
                $logger->error('❌ Erreur base de données', ['message' => $e->getMessage()]);
                if (str_contains($e->getMessage(), 'foreign key constraint')) {
                    $this->addFlash('error', '⚠️ Erreur de configuration. Contactez l\'administrateur.');
                } else {
                    $this->addFlash('error', '💥 Erreur lors de la sauvegarde : ' . $e->getMessage());
                }

            } catch (\Exception $e) {
                $logger->error('❌ Erreur générale inscription', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
                $this->addFlash('error', '💥 Une erreur est survenue : ' . $e->getMessage());
            }

        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $logger->warning('⚠️ Formulaire invalide', [
                'errors' => (string) $form->getErrors(true, false)
            ]);
        }

        return $this->render('auth/register/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * API AJAX — Analyse de la force du mot de passe
     */
    #[Route('/api/check-password-strength', name: 'api_check_password_strength', methods: ['POST'])]
    public function checkPasswordStrength(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data     = json_decode($request->getContent(), true);
            $password = $data['password'] ?? '';

            if (empty($password)) {
                return new JsonResponse(['score' => 0, 'level' => 'Vide', 'color' => 'gray', 'suggestions' => ['Entrez un mot de passe']]);
            }

            return new JsonResponse($this->aiService->analyzePasswordStrength($password));

        } catch (\Exception $e) {
            $logger->error('Erreur API password strength', ['message' => $e->getMessage()]);
            return new JsonResponse(['score' => 50, 'level' => 'Moyen', 'color' => 'orange', 'suggestions' => []]);
        }
    }
}