<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Entity\ConfidentialiteUtilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {

        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'attr' => ['novalidate' => 'novalidate'] // Désactive HTML5
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                // 🔹 Récupération rôle
                $roleChoisi = $form->get('role')->getData();

                // 🔐 Vérification ADMIN
                if ($roleChoisi === 'ROLE_ADMIN') {

                    $codeSecret = $request->request->get('code_admin_secret');

                    // ⚠️ Mets ça dans .env
                    $CODE_SECRET_ADMIN = $_ENV['ADMIN_SECRET_CODE'] ?? 'CHANGE_ME';

                    if ($codeSecret !== $CODE_SECRET_ADMIN) {

                        $this->addFlash('error', 'Code administrateur incorrect.');

                        $logger->warning('Tentative Admin invalide', [
                            'email' => $user->getEmail(),
                            'ip' => $request->getClientIp()
                        ]);

                        return $this->redirectToRoute('app_register');
                    }
                }

                // 🔐 Hash password
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setPassword($hashedPassword);
                $user->setRoles([$roleChoisi]);
                $user->setStatut('actif');

                // 🔒 Création Confidentialité
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
                    'message' => $e->getMessage()
                ]);

                $this->addFlash('error', 'Une erreur est survenue.');
            }
        }

        return $this->render('auth/register/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
