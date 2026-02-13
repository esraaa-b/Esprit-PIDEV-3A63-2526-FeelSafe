<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    /**
     * Liste de tous les utilisateurs
     */
    #[Route('', name: 'admin_users_list')]
    public function list(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        
        // Statistiques par rôle
        $stats = [
            'total' => count($users),
            'clients' => 0,
            'professionnels' => 0,
            'admins' => 0,
        ];
        
        foreach ($users as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles)) {
                $stats['admins']++;
            } elseif (in_array('ROLE_PROFESSIONNEL', $roles)) {
                $stats['professionnels']++;
            } else {
                $stats['clients']++;
            }
        }

        return $this->render('admin/dashboard/users_list.html.twig', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Créer un nouvel utilisateur/administrateur
     */
    #[Route('/create', name: 'admin_create_admin')]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $prenom = $request->request->get('prenom');
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');
            $role = $request->request->get('role');

            // Validation basique
            if (empty($prenom) || empty($nom) || empty($email) || empty($password) || empty($role)) {
                $this->addFlash('error', '❌ Tous les champs obligatoires doivent être remplis.');
                return $this->redirectToRoute('admin_create_admin');
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('error', '❌ Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('admin_create_admin');
            }

            // Vérifier si l'email existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', '❌ Cet email est déjà utilisé.');
                return $this->redirectToRoute('admin_create_admin');
            }

            // Créer le nouvel utilisateur
            $user = new User();
            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setTelephone($telephone);
            $user->setRoles([$role]);
            
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', "✅ L'utilisateur {$prenom} {$nom} a été créé avec succès.");
            return $this->redirectToRoute('admin_users_list');
        }

        return $this->render('admin/users/create.html.twig');
    }

    /**
     * Voir le détail d'un utilisateur
     */
    #[Route('/{id}', name: 'admin_users_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Changer le statut d'un utilisateur (actif/inactif)
     */
    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        User $user,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            $newStatus = $user->getStatut() === 'actif' ? 'inactif' : 'actif';
            $user->setStatut($newStatus);
            $entityManager->flush();

            $this->addFlash('success', "✅ Statut modifié : {$newStatus}");
        }

        return $this->redirectToRoute('admin_users_list');
    }

    /**
     * Supprimer un utilisateur
     */
    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(
        User $user,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            
            // Empêcher la suppression du dernier admin
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $adminCount = $entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->where('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_ADMIN%')
                    ->getQuery()
                    ->getSingleScalarResult();
                    
                if ($adminCount <= 1) {
                    $this->addFlash('error', '❌ Impossible de supprimer le dernier administrateur.');
                    return $this->redirectToRoute('admin_users_list');
                }
            }
            
            $email = $user->getEmail();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', "✅ L'utilisateur {$email} a été supprimé avec succès.");
        }

        return $this->redirectToRoute('admin_users_list');
    }
}