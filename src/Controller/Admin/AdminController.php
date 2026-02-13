<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    /**
     * 📊 Dashboard principal - Affichage de tous les utilisateurs
     */
    #[Route('', name: 'admin_home')]
    #[Route('/', name: 'admin_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        // Récupérer TOUS les utilisateurs
        $users = $userRepository->findAll();
        
        // Initialiser les compteurs
        $totalUsers = count($users);
        $totalClients = 0;
        $totalProfessionnels = 0;
        $totalAdmins = 0;

        // Calculer les statistiques
        foreach ($users as $user) {
            $roles = $user->getRoles();
            
            if (in_array('ROLE_ADMIN', $roles)) {
                $totalAdmins++;
            } elseif (in_array('ROLE_PROFESSIONNEL', $roles)) {
                $totalProfessionnels++;
            } else {
                $totalClients++;
            }
        }

        // Préparer le tableau de stats
        $stats = [
            'total_users' => $totalUsers,
            'total_clients' => $totalClients,
            'total_professionnels' => $totalProfessionnels,
            'total_admins' => $totalAdmins,
        ];

        // Rendu de la vue avec les données
        return $this->render('admin/dashboard/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * ✏️ Modifier un utilisateur
     */
    #[Route('/user/{id}/edit', name: 'admin_user_edit', methods: ['POST'])]
    public function editUser(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            // Vérifier le token CSRF
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('user_edit_' . $user->getId(), $token)) {
                $this->addFlash('error', '❌ Token de sécurité invalide.');
                return $this->redirectToRoute('admin_dashboard');
            }

            // Récupérer les données du formulaire
            $prenom = trim($request->request->get('prenom'));
            $nom = trim($request->request->get('nom'));
            $email = trim($request->request->get('email'));
            $telephone = trim($request->request->get('telephone'));
            $statut = $request->request->get('statut');
            $roles = $request->request->all('roles');

            // Validation basique
            if (empty($prenom) || empty($nom) || empty($email)) {
                $this->addFlash('error', '❌ Le prénom, nom et email sont obligatoires.');
                return $this->redirectToRoute('admin_dashboard');
            }

            // Validation email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', '❌ L\'adresse email n\'est pas valide.');
                return $this->redirectToRoute('admin_dashboard');
            }

            // Mettre à jour l'utilisateur
            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);
            $user->setStatut($statut);

            // Gérer les rôles (au moins un rôle requis)
            if (!empty($roles) && is_array($roles)) {
                $user->setRoles($roles);
            } else {
                $user->setRoles(['ROLE_CLIENT']); // Rôle par défaut
            }

            // Sauvegarder
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                '✅ L\'utilisateur %s a été modifié avec succès !',
                $user->getFullName()
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * 🗑️ Supprimer un utilisateur
     */
    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            // Vérifier le token CSRF
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('user_delete_' . $user->getId(), $token)) {
                $this->addFlash('error', '❌ Token de sécurité invalide.');
                return $this->redirectToRoute('admin_dashboard');
            }

            /** @var User $currentUser */
            $currentUser = $this->getUser();

            // Protection : ne pas supprimer son propre compte
            if ($user->getId() === $currentUser->getId()) {
                $this->addFlash('error', '❌ Vous ne pouvez pas supprimer votre propre compte !');
                return $this->redirectToRoute('admin_dashboard');
            }

            // Sauvegarder le nom avant suppression
            $userName = $user->getFullName();

            // Supprimer l'utilisateur
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                '✅ L\'utilisateur %s a été supprimé avec succès.',
                $userName
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * 📥 Exporter les utilisateurs en CSV
     */
    #[Route('/users/export-csv', name: 'admin_users_export_csv')]
    public function exportCsv(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        $response = new StreamedResponse(function() use ($users) {
            $handle = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes du CSV
            fputcsv($handle, [
                'ID',
                'Prénom',
                'Nom',
                'Email',
                'Téléphone',
                'Rôles',
                'Statut',
                'Date d\'inscription'
            ], ';');

            // Données
            foreach ($users as $user) {
                // Convertir les rôles en texte lisible
                $roles = $user->getRoles();
                $roleText = '';
                
                if (in_array('ROLE_ADMIN', $roles)) {
                    $roleText = 'Administrateur';
                } elseif (in_array('ROLE_PROFESSIONNEL', $roles)) {
                    $roleText = 'Professionnel';
                } else {
                    $roleText = 'Client';
                }

                fputcsv($handle, [
                    $user->getId(),
                    $user->getPrenom(),
                    $user->getNom(),
                    $user->getEmail(),
                    $user->getTelephone() ?? '',
                    $roleText,
                    $user->getStatut(),
                    $user->getDateCreation() ? $user->getDateCreation()->format('d/m/Y H:i') : ''
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="utilisateurs_' . date('Y-m-d_H-i') . '.csv"');

        return $response;
    }
}