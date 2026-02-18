<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Entity\ConfidentialiteUtilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

#[Route('/admin/security')]
class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        CsrfTokenManagerInterface $csrfTokenManager,
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    #[Route('/user/create', name: 'admin_security_user_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_create', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $nom           = trim($request->request->get('nom', ''));
            $prenom        = trim($request->request->get('prenom', ''));
            $email         = trim($request->request->get('email', ''));
            $telephone     = trim($request->request->get('telephone', ''));
            $statut        = $request->request->get('statut', 'actif');
            $roles         = $request->request->all('roles');
            $plainPassword = $request->request->get('mot_de_passe', '');

            // Validation des champs obligatoires
            if (empty($nom)) {
                throw new \Exception('Le champ "Nom" est obligatoire.');
            }
            if (empty($prenom)) {
                throw new \Exception('Le champ "Prénom" est obligatoire.');
            }
            if (empty($email)) {
                throw new \Exception('Le champ "Email" est obligatoire.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("L'adresse email n'est pas valide.");
            }

            // Vérifier unicité email
            $existingUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                throw new \Exception('Cet email est déjà utilisé par un autre utilisateur.');
            }

            if (empty($plainPassword)) {
                throw new \Exception('Le mot de passe est obligatoire.');
            }
            if (strlen($plainPassword) < 6) {
                throw new \Exception('Le mot de passe doit contenir au moins 6 caractères.');
            }

            if (!in_array($statut, ['actif', 'inactif'])) {
                throw new \Exception('Le statut doit être "actif" ou "inactif".');
            }

            // Création de l'entité
            $user = new Utilisateur();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);
            $user->setStatut($statut);
            $user->setRoles(!empty($roles) ? $roles : ['ROLE_CLIENT']);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setMotDePasse($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', "Compte de {$prenom} {$nom} créé avec succès.");

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/user/{id}/update', name: 'admin_security_user_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_edit_' . $id, $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            /** @var Utilisateur|null $user */
            $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);

            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            // 📝 Sauvegarder les anciennes valeurs pour comparer
            $oldEmail = $user->getEmail();
            $oldNom = $user->getNom();
            $oldPrenom = $user->getPrenom();
            $oldTelephone = $user->getTelephone();
            $oldStatut = $user->getStatut();
            $oldRoles = $user->getRoles();

            $nom    = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email  = trim($request->request->get('email', ''));

            if (empty($nom) || empty($prenom) || empty($email)) {
                throw new \Exception('Les champs Nom, Prénom et Email sont obligatoires');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("L'adresse email n'est pas valide");
            }

            $existingUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                throw new \Exception('Cet email est déjà utilisé par un autre utilisateur');
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($request->request->get('telephone'));
            $user->setStatut($request->request->get('statut', 'actif'));

            $roles = $request->request->all('roles');
            if (!empty($roles)) {
                $user->setRoles($roles);
            }

            $passwordChanged = false;
            $newPlainPassword = null; // Pour stocker le mot de passe en clair
            $plainPassword = $request->request->get('mot_de_passe', '');
            if (!empty($plainPassword)) {
                if (strlen($plainPassword) < 6) {
                    throw new \Exception('Le mot de passe doit contenir au moins 6 caractères');
                }
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setMotDePasse($hashedPassword);
                $passwordChanged = true;
                $newPlainPassword = $plainPassword; // Sauvegarder le mot de passe en clair
            }

            $this->entityManager->flush();

            // ✅ Déterminer quels changements ont été effectués (SANS LE MOT DE PASSE)
            $changes = [];
            if ($oldNom !== $nom || $oldPrenom !== $prenom) {
                $changes[] = "Nom/Prénom modifié en: {$prenom} {$nom}";
            }
            if ($oldEmail !== $email) {
                $changes[] = "Email modifié en: {$email}";
            }
            if ($oldTelephone !== $user->getTelephone()) {
                $changes[] = "Téléphone modifié en: " . ($user->getTelephone() ?: 'Non renseigné');
            }
            if ($oldStatut !== $user->getStatut()) {
                $changes[] = "Statut modifié en: " . $user->getStatut();
            }
            if ($oldRoles !== $user->getRoles()) {
                $changes[] = "Rôle modifié";
            }
            // ⚠️ NE PAS AJOUTER LE MOT DE PASSE DANS LA LISTE DES CHANGEMENTS
            // On informe juste qu'il a été changé sans montrer la valeur

            // ✅ Envoyer l'email de notification uniquement si des modifications ont été faites
            if (!empty($changes) || $passwordChanged) {
                try {
                    $emailMessage = (new TemplatedEmail())
                        ->from(new Address('noreply@feelsafe.com', 'FeelSafe'))
                        ->to(new Address($user->getEmail(), $user->getFullName()))
                        ->subject('Modification de votre compte - FeelSafe')
                        ->htmlTemplate('emails/account_modified.html.twig')
                        ->context([
                            'user' => $user,
                            'changes' => $changes,
                            'passwordChanged' => $passwordChanged,
                            'newPassword' => $newPlainPassword, // Envoyer le mot de passe en clair
                        ]);

                    $this->mailer->send($emailMessage);

                    $this->logger->info('✅ Email de modification envoyé', [
                        'user' => $user->getEmail(),
                        'changes' => count($changes),
                        'passwordChanged' => $passwordChanged
                    ]);

                } catch (\Exception $e) {
                    $this->logger->error('❌ Erreur envoi email de modification', [
                        'user' => $user->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                    // Ne pas bloquer la modification même si l'email échoue
                }
            }

            $this->addFlash('success', 'Utilisateur modifié avec succès.' . (!empty($changes) || $passwordChanged ? ' Un email de notification a été envoyé.' : ''));

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_delete_' . $id, $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            /** @var Utilisateur|null $user */
            $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);

            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            /** @var Utilisateur|null $currentUser */
            $currentUser = $this->getUser();
            
            if ($currentUser && $user->getId() === $currentUser->getId()) {
                throw new \Exception('Vous ne pouvez pas supprimer votre propre compte');
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès');

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}