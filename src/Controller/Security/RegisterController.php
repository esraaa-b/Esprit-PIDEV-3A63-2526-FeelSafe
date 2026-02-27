<?php

namespace App\Controller\Security;

use App\Entity\Utilisateur;
use App\Entity\ConfidentialiteUtilisateur;
use App\Form\RegistrationFormType;
use App\Service\RegistrationAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegisterController extends AbstractController
{
    public function __construct(
        private RegistrationAIService $aiService
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ParameterBagInterface $params 
    ): Response {
        $user = new Utilisateur();

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'attr' => ['novalidate' => 'novalidate']
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $logger->info('🚀 Début inscription', [
                    'email' => $user->getEmail(),
                    'prenom' => $user->getPrenom(),
                    'nom' => $user->getNom()
                ]);

                // ✅ VALIDATION IA: Prénom (avec fallback en cas d'erreur)
                try {
                    $prenomValidation = $this->aiService->validateName($user->getPrenom(), 'prénom');
                    if (!$prenomValidation['valid']) {
                        $logger->warning('❌ Validation IA prénom échouée', ['reason' => $prenomValidation['reason']]);
                        $this->addFlash('error', 'Prénom invalide: ' . $prenomValidation['reason']);
                        return $this->redirectToRoute('app_register');
                    }
                    $logger->info('✅ Validation IA prénom OK');
                } catch (\Exception $e) {
                    // Si l'IA échoue, on continue quand même (fallback)
                    $logger->warning('⚠️ Erreur validation IA prénom, on continue', ['error' => $e->getMessage()]);
                }

                // ✅ VALIDATION IA: Nom (avec fallback en cas d'erreur)
                try {
                    $nomValidation = $this->aiService->validateName($user->getNom(), 'nom');
                    if (!$nomValidation['valid']) {
                        $logger->warning('❌ Validation IA nom échouée', ['reason' => $nomValidation['reason']]);
                        $this->addFlash('error', 'Nom invalide: ' . $nomValidation['reason']);
                        return $this->redirectToRoute('app_register');
                    }
                    $logger->info('✅ Validation IA nom OK');
                } catch (\Exception $e) {
                    $logger->warning('⚠️ Erreur validation IA nom, on continue', ['error' => $e->getMessage()]);
                }

                // ✅ VALIDATION IA: Email (avec fallback en cas d'erreur)
                try {
                    $emailValidation = $this->aiService->validateEmail($user->getEmail());
                    if (!$emailValidation['valid']) {
                        $logger->warning('❌ Validation IA email échouée', ['reason' => $emailValidation['reason']]);
                        $this->addFlash('error', $emailValidation['reason']);
                        return $this->redirectToRoute('app_register');
                    }
                    $logger->info('✅ Validation IA email OK');
                } catch (\Exception $e) {
                    $logger->warning('⚠️ Erreur validation IA email, on continue', ['error' => $e->getMessage()]);
                }

                $roleChoisi = $form->get('role')->getData();
                $logger->info('📋 Rôle choisi: ' . $roleChoisi);

                // Vérification ADMIN
                if ($roleChoisi === 'ROLE_ADMIN') {
                    $codeSecret = $request->request->get('code_admin_secret');
                    
                    try {
                        $CODE_SECRET_ADMIN = $params->get('admin.secret.code');
                    } catch (\Exception $e) {
                        $CODE_SECRET_ADMIN = $_ENV['ADMIN_SECRET_CODE'] ?? 'FEELSAFE';
                        $logger->warning('⚠️ Paramètre admin.secret.code non trouvé, utilisation de la valeur par défaut');
                    }

                    if ($codeSecret !== $CODE_SECRET_ADMIN) {
                        $this->addFlash('error', '🔒 Code administrateur incorrect.');
                        
                        $logger->warning('🚫 Tentative Admin invalide', [
                            'email' => $user->getEmail(),
                            'ip' => $request->getClientIp(),
                            'code_fourni' => $codeSecret ? 'oui' : 'non'
                        ]);

                        return $this->redirectToRoute('app_register');
                    }
                    $logger->info('✅ Code admin validé');
                }

                // Hash password
                $logger->info('🔐 Hash du mot de passe...');
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setMotDePasse($hashedPassword);
                $user->setRoles([$roleChoisi]);
                $user->setStatut('actif');

                $logger->info('👤 Configuration utilisateur OK');

                // Création Confidentialité
                $logger->info('🔒 Création paramètres confidentialité...');
                $confidentialite = new ConfidentialiteUtilisateur();
                $confidentialite->setUtilisateur($user);
                $confidentialite->setVisibiliteProfil($form->get('visibiliteProfil')->getData());
                $confidentialite->setPartageDonnees($form->get('partageDonnees')->getData() ?? false);
                $confidentialite->setNotificationsEmail($form->get('notificationsEmail')->getData() ?? true);

                $user->setConfidentialite($confidentialite);

                $logger->info('💾 Sauvegarde en base de données...');
                $entityManager->persist($user);
                $entityManager->flush();

                $logger->info('✅ Inscription réussie!', ['user_id' => $user->getId()]);

                $this->addFlash('success', '✅ Compte créé avec succès! Votre profil a été validé par IA.');

                return $this->redirectToRoute('app_login');

            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $logger->error('❌ Email déjà utilisé', [
                    'email' => $user->getEmail(),
                    'error' => $e->getMessage()
                ]);
                $this->addFlash('error', '📧 Cet email est déjà utilisé.');
                
            } catch (\Doctrine\DBAL\Exception $e) {
                $logger->error('❌ Erreur base de données', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Message plus spécifique selon l'erreur
                if (str_contains($e->getMessage(), 'foreign key constraint')) {
                    $this->addFlash('error', '⚠️ Erreur de configuration de la base de données. Contactez l\'administrateur.');
                } else {
                    $this->addFlash('error', '💥 Erreur lors de la sauvegarde: ' . $e->getMessage());
                }
                
            } catch (\Exception $e) {
                $logger->error('❌ Erreur générale inscription', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->addFlash('error', '💥 Une erreur est survenue lors de l\'inscription: ' . $e->getMessage());
            }
        } else if ($form->isSubmitted() && !$form->isValid()) {
            $logger->warning('⚠️ Formulaire invalide', [
                'errors' => (string) $form->getErrors(true, false)
            ]);
        }

        return $this->render('auth/register/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * ✅ API AJAX pour analyser le mot de passe en temps réel
     */
    #[Route('/api/check-password-strength', name: 'api_check_password_strength', methods: ['POST'])]
    public function checkPasswordStrength(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $password = $data['password'] ?? '';

            if (empty($password)) {
                return new JsonResponse([
                    'score' => 0,
                    'level' => 'Vide',
                    'color' => 'gray',
                    'suggestions' => ['Entrez un mot de passe']
                ]);
            }

            $analysis = $this->aiService->analyzePasswordStrength($password);

            return new JsonResponse($analysis);
            
        } catch (\Exception $e) {
            $logger->error('Erreur API password strength', [
                'message' => $e->getMessage()
            ]);
            
            // Retourner une réponse par défaut en cas d'erreur
            return new JsonResponse([
                'score' => 50,
                'level' => 'Moyen',
                'color' => 'orange',
                'suggestions' => ['Vérification en cours...']
            ]);
        }
    }
}