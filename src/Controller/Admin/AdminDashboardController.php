<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Récupérer tous les utilisateurs
        $users = $this->userRepository->findAll();

        // Calculer les statistiques
        $stats = [
            'total_users'          => count($users),
            'total_clients'        => $this->userRepository->countByRole('ROLE_CLIENT'),
            'total_professionnels' => $this->userRepository->countByRole('ROLE_PROFESSIONNEL'),
            'total_admins'         => $this->userRepository->countByRole('ROLE_ADMIN'),
        ];

        // Générer les tokens CSRF pour chaque utilisateur
        $csrfTokens = [];
        foreach ($users as $user) {
            $csrfTokens['edit_'   . $user->getId()] = $this->csrfTokenManager->getToken('user_edit_'   . $user->getId())->getValue();
            $csrfTokens['delete_' . $user->getId()] = $this->csrfTokenManager->getToken('user_delete_' . $user->getId())->getValue();
        }

        // Token CSRF pour le profil admin
        $csrfTokens['admin_profile'] = $this->csrfTokenManager->getToken('admin_profile_edit')->getValue();

        // Token CSRF pour la création d'un utilisateur
        $csrfTokens['user_create'] = $this->csrfTokenManager->getToken('user_create')->getValue();

        return $this->render('admin/dashboard/index.html.twig', [
            'users'       => $users,
            'stats'       => $stats,
            'csrf_tokens' => $csrfTokens,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit', methods: ['POST'])]
    public function editUser(int $id, Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_edit_' . $id, $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Récupération et validation des données
        $nom       = trim($request->request->get('nom', ''));
        $prenom    = trim($request->request->get('prenom', ''));
        $email     = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));
        $statut    = $request->request->get('statut', '');
        $roles     = $request->request->all('roles');

        if (empty($nom)) {
            $this->addFlash('error', 'Le champ "Nom" est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (empty($prenom)) {
            $this->addFlash('error', 'Le champ "Prénom" est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (empty($email)) {
            $this->addFlash('error', 'Le champ "Email" est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', "L'adresse email n'est pas valide.");
            return $this->redirectToRoute('admin_dashboard');
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($statut)) {
            $this->addFlash('error', 'Le champ "Statut" est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (!in_array($statut, ['actif', 'inactif'])) {
            $this->addFlash('error', 'Le statut doit être "actif" ou "inactif".');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (empty($roles)) {
            $this->addFlash('error', 'Au moins un rôle doit être sélectionné.');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);
            $user->setStatut($statut);
            $user->setRoles($roles);

            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_delete_' . $id, $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/users/export-csv', name: 'admin_users_export_csv')]
    public function exportCsv(): Response
    {
        $users = $this->userRepository->findAll();

        $csv   = [];
        $csv[] = ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Rôles', 'Statut', 'Date Inscription'];

        foreach ($users as $user) {
            $csv[] = [
                $user->getId(),
                $user->getNom(),
                $user->getPrenom(),
                $user->getEmail(),
                $user->getTelephone() ?? '',
                implode(', ', $user->getRoles()),
                $user->getStatut(),
                $user->getDateCreation() ? $user->getDateCreation()->format('d/m/Y H:i:s') : '',
            ];
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="utilisateurs_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://temp', 'r+');
        // BOM UTF-8 pour Excel
        fputs($output, "\xEF\xBB\xBF");
        foreach ($csv as $row) {
            fputcsv($output, $row, ';');
        }
        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    #[Route('/profile/edit', name: 'admin_profile_edit', methods: ['POST'])]
    public function editProfile(Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('admin_profile_edit', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        $nom       = trim($request->request->get('nom', ''));
        $prenom    = trim($request->request->get('prenom', ''));
        $email     = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));

        if (empty($nom)) {
            $this->addFlash('error', 'Le champ "Nom" est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (empty($prenom)) {
            $this->addFlash('error', 'Le champ "Prénom" est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (empty($email)) {
            $this->addFlash('error', 'Le champ "Email" est obligatoire.');
            return $this->redirectToRoute('admin_dashboard');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', "L'adresse email n'est pas valide.");
            return $this->redirectToRoute('admin_dashboard');
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre profil a été modifié avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}