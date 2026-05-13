<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private UtilisateurRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

   #[Route('/dashboard', name: 'admin_dashboard')]
public function index(): Response
{
    // ✅ One SQL query for stats instead of loading all users
    $stats = $this->userRepository->getStatsByRole();

    // ✅ Paginated instead of findAll()
    $users = $this->userRepository->findAllPaginated(100);

    // CSRF tokens (unchanged)
    $csrfTokens = [];
    foreach ($users as $user) {
        $csrfTokens['edit_'   . $user->getId()] = $this->csrfTokenManager->getToken('user_edit_'   . $user->getId())->getValue();
        $csrfTokens['delete_' . $user->getId()] = $this->csrfTokenManager->getToken('user_delete_' . $user->getId())->getValue();
    }
    $csrfTokens['admin_profile'] = $this->csrfTokenManager->getToken('admin_profile_edit')->getValue();
    $csrfTokens['user_create']   = $this->csrfTokenManager->getToken('user_create')->getValue();

    return $this->render('admin/dashboard/index.html.twig', [
        'users'       => $users,
        'stats'       => $stats,
        'csrf_tokens' => $csrfTokens,
    ]);
}

    #[Route('/security/user/{id}/update', name: 'admin_security_user_update', methods: ['POST'])]
    public function updateUser(int $id, Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_edit_' . $id, $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', '❌ Token de sécurité invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', '❌ Utilisateur non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Récupération des données
        $nom       = trim($request->request->get('nom', ''));
        $prenom    = trim($request->request->get('prenom', ''));
        $email     = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));
        $statut    = $request->request->get('statut', '');
        $roles     = $request->request->all('roles');

        // ✅ VALIDATION DES CHAMPS
        if (empty($nom)) {
            $this->addFlash('error', '❌ Le champ "Nom" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($prenom)) {
            $this->addFlash('error', '❌ Le champ "Prénom" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($email)) {
            $this->addFlash('error', '❌ Le champ "Email" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', '❌ L\'adresse email n\'est pas valide');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($statut)) {
            $this->addFlash('error', '❌ Le champ "Statut" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($roles)) {
            $this->addFlash('error', '❌ Au moins un rôle doit être sélectionné');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Vérifier l'unicité de l'email
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', '❌ Cet email est déjà utilisé par un autre utilisateur');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);
            // ⛔ Statut non modifiable manuellement — géré automatiquement via last_login
            $user->setRoles($roles);

            $this->entityManager->flush();

            // ✅ MESSAGE DE SUCCÈS
            $this->addFlash('success', '✅ L\'utilisateur ' . $user->getFullName() . ' a été modifié avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/security/user/create', name: 'admin_security_user_create', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_create', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', '❌ Token de sécurité invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Récupération des données
        $nom        = trim($request->request->get('nom', ''));
        $prenom     = trim($request->request->get('prenom', ''));
        $email      = trim($request->request->get('email', ''));
        $telephone  = trim($request->request->get('telephone', ''));
        $statut     = $request->request->get('statut', 'actif');
        $roles      = $request->request->all('roles');
        $motDePasse = $request->request->get('mot_de_passe', '');

        // ✅ VALIDATION DES CHAMPS
        if (empty($nom)) {
            $this->addFlash('error', '❌ Le champ "Nom" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($prenom)) {
            $this->addFlash('error', '❌ Le champ "Prénom" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($email)) {
            $this->addFlash('error', '❌ Le champ "Email" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', '❌ L\'adresse email n\'est pas valide');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($motDePasse)) {
            $this->addFlash('error', '❌ Le champ "Mot de passe" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (strlen($motDePasse) < 6) {
            $this->addFlash('error', '❌ Le mot de passe doit contenir au moins 6 caractères');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($roles)) {
            $this->addFlash('error', '❌ Au moins un rôle doit être sélectionné');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Vérifier l'unicité de l'email
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', '❌ Cet email est déjà utilisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $user = new Utilisateur();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);
            $user->setStatut($statut);
            $user->setRoles($roles);

            // Hasher le mot de passe avec le PasswordHasher
            $hashedPassword = $this->passwordHasher->hashPassword($user, $motDePasse);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // ✅ MESSAGE DE SUCCÈS
            $this->addFlash('success', '✅ L\'utilisateur ' . $user->getFullName() . ' a été créé avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/security/user/{id}/delete', name: 'admin_security_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('user_delete_' . $id, $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', '❌ Token de sécurité invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', '❌ Utilisateur non trouvé');
            return $this->redirectToRoute('admin_dashboard');
        }

        /** @var Utilisateur $currentUser */
        $currentUser = $this->getUser();
        
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', '❌ Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $userName = $user->getFullName();
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            // ✅ MESSAGE DE SUCCÈS
            $this->addFlash('success', '✅ L\'utilisateur ' . $userName . ' a été supprimé avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * ✅ API : Retourne les utilisateurs inactifs depuis N jours (JSON)
     * Utilisée par le bloc IA du dashboard pour analyser les inactifs.
     */
    #[Route('/users/inactifs', name: 'admin_users_inactifs_api', methods: ['GET'])]
    public function getInactifsApi(Request $request): JsonResponse
    {
        $seuil = max(1, (int) $request->query->get('jours', 7));
        $depuis = new \DateTime("-{$seuil} days");

        $qb = $this->entityManager->createQueryBuilder();
        $inactifs = $qb->select('u')
            ->from(\App\Entity\Utilisateur::class, 'u')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('u.lastLogin'),
                    $qb->expr()->lt('u.lastLogin', ':depuis')
                )
            )
            ->setParameter('depuis', $depuis)
            ->orderBy('u.lastLogin', 'ASC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $data = array_map(function (\App\Entity\Utilisateur $u) use ($now) {
            $lastLogin = $u->getLastLogin();
            $joursInactif = $lastLogin
                ? (int) $now->diff($lastLogin)->days
                : null;
            return [
                'id'           => $u->getId(),
                'nom'          => $u->getFullName(),
                'email'        => $u->getEmail(),
                'roles'        => $u->getRoles(),
                'statut'       => $u->getStatut(),
                'last_login'   => $lastLogin ? $lastLogin->format('d/m/Y H:i') : null,
                'jours_inactif' => $joursInactif,
            ];
        }, $inactifs);

        return new JsonResponse([
            'total_inactifs' => count($data),
            'seuil_jours'    => $seuil,
            'utilisateurs'   => $data,
        ]);
    }

    #[Route('/profile/edit', name: 'admin_profile_edit', methods: ['POST'])]
    public function editProfile(Request $request): Response
    {
        // Vérifier le token CSRF
        $token = new CsrfToken('admin_profile_edit', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', '❌ Token de sécurité invalide');
            return $this->redirectToRoute('admin_dashboard');
        }

        /** @var Utilisateur $user */
        $user = $this->getUser();

        $nom       = trim($request->request->get('nom', ''));
        $prenom    = trim($request->request->get('prenom', ''));
        $email     = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));

        // ✅ VALIDATION
        if (empty($nom)) {
            $this->addFlash('error', '❌ Le champ "Nom" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($prenom)) {
            $this->addFlash('error', '❌ Le champ "Prénom" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (empty($email)) {
            $this->addFlash('error', '❌ Le champ "Email" est obligatoire');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', '❌ L\'adresse email n\'est pas valide');
            return $this->redirectToRoute('admin_dashboard');
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $this->addFlash('error', '❌ Cet email est déjà utilisé');
            return $this->redirectToRoute('admin_dashboard');
        }

        try {
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($telephone ?: null);

            $this->entityManager->flush();

            // ✅ MESSAGE DE SUCCÈS
            $this->addFlash('success', '✅ Votre profil a été modifié avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

#[Route('/users/export-csv', name: 'admin_users_export_csv')]
public function exportCsv(): Response
{
    // ✅ Use an iterator instead of loading everything into memory
    $users = $this->userRepository->createQueryBuilder('u')
        ->orderBy('u.dateCreation', 'DESC')
        ->getQuery()
        ->toIterable(); // streams results row by row, no memory spike

    $response = new Response();
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="utilisateurs_' . date('Y-m-d_H-i-s') . '.csv"');

    $output = fopen('php://temp', 'r+');
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Rôles', 'Statut', 'Date Inscription'], ';');

    foreach ($users as $user) {
        fputcsv($output, [
            $user->getId(),
            $user->getNom(),
            $user->getPrenom(),
            $user->getEmail(),
            $user->getTelephone() ?? '',
            implode(', ', $user->getRoles()),
            $user->getStatut(),
            $user->getDateCreation()?->format('d/m/Y H:i:s') ?? '',
        ], ';');

        // Detach entity from memory after each row
        $this->entityManager->detach($user);
    }

    rewind($output);
    $response->setContent(stream_get_contents($output));
    fclose($output);

    return $response;
}
}