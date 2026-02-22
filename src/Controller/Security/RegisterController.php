<?php

namespace App\Controller\Security;

use App\Entity\Utilisateur;
use App\Entity\ConfidentialiteUtilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegisterController extends AbstractController
{
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
                $roleChoisi = $form->get('role')->getData();

                // Vérification ADMIN
                if ($roleChoisi === 'ROLE_ADMIN') {
                    $codeSecret = $request->request->get('code_admin_secret');
                    
                    // Récupération sécurisée avec valeur par défaut
                    try {
                        $CODE_SECRET_ADMIN = $params->get('admin.secret.code');
                    } catch (\Exception $e) {
                        // Fallback pour le développement
                        $CODE_SECRET_ADMIN = $_ENV['ADMIN_SECRET_CODE'] ?? 'FEELSAFE';
                        $logger->warning('Paramètre admin.secret.code non trouvé, utilisation du fallback');
                    }

                    if ($codeSecret !== $CODE_SECRET_ADMIN) {
                        $this->addFlash('error', 'Code administrateur incorrect.');
                        
                        $logger->warning('Tentative Admin invalide', [
                            'email' => $user->getEmail(),
                            'ip' => $request->getClientIp()
                        ]);

                        return $this->redirectToRoute('app_register');
                    }
                }

                // Hash password
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setMotDePasse($hashedPassword);
                $user->setRoles([$roleChoisi]);
                $user->setStatut('actif');

                // Création Confidentialité
                $confidentialite = new ConfidentialiteUtilisateur();
                $confidentialite->setUtilisateur($user);
                $confidentialite->setVisibiliteProfil($form->get('visibiliteProfil')->getData());
                $confidentialite->setPartageDonnees($form->get('partageDonnees')->getData() ?? false);
                $confidentialite->setNotificationsEmail($form->get('notificationsEmail')->getData() ?? true);

                $user->setConfidentialite($confidentialite);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Compte créé avec succès.');

                return $this->redirectToRoute('app_login');

            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
            } catch (\Exception $e) {
                $logger->error('Erreur inscription', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
            }
        }

        return $this->render('auth/register/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}