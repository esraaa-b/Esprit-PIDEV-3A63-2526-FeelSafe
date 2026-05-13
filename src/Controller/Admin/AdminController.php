<?php
// =======================================================
// CHEMIN : src/Controller/Admin/AdminController.php
// ⚠️  SUPPRIMER AdminDashboardController.php si il existe !
// =======================================================

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private CsrfTokenManagerInterface   $csrfTokenManager,
        private UserPasswordHasherInterface  $passwordHasher
    ) {}

    // DASHBOARD
    #[Route('', name: 'admin_home')]
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(UtilisateurRepository $repo): Response
    {
        $users = $repo->findAll();
        $totalClients = $totalPros = $totalAdmins = 0;
        foreach ($users as $u) {
            $r = $u->getRoles();
            if (in_array('ROLE_ADMIN', $r)) $totalAdmins++;
            elseif (in_array('ROLE_PROFESSIONNEL', $r)) $totalPros++;
            else $totalClients++;
        }
        $stats = [
            'total_users'          => count($users),
            'total_clients'        => $totalClients,
            'total_professionnels' => $totalPros,
            'total_admins'         => $totalAdmins,
        ];
        $csrf = [];
        foreach ($users as $u) {
            $csrf['edit_'   . $u->getId()] = $this->csrfTokenManager->getToken('user_edit_'   . $u->getId())->getValue();
            $csrf['delete_' . $u->getId()] = $this->csrfTokenManager->getToken('user_delete_' . $u->getId())->getValue();
        }
        $csrf['user_create']   = $this->csrfTokenManager->getToken('user_create')->getValue();
        $csrf['admin_profile'] = $this->csrfTokenManager->getToken('admin_profile_edit')->getValue();
        return $this->render('admin/dashboard/index.html.twig', [
            'users' => $users, 'stats' => $stats, 'csrf_tokens' => $csrf,
        ]);
    }

    // CRÉER
    #[Route('/security/user/create', name: 'admin_security_user_create', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('user_create', $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token invalide'); return $this->redirectToRoute('admin_dashboard');
        }
        $nom = trim($request->request->get('nom', '')); $prenom = trim($request->request->get('prenom', ''));
        $email = trim($request->request->get('email', '')); $tel = trim($request->request->get('telephone', ''));
        $statut = $request->request->get('statut', 'actif'); $roles = $request->request->all('roles');
        $mdp = $request->request->get('mot_de_passe', '');
        if (empty($nom) || empty($prenom) || empty($email)) { $this->addFlash('error', '❌ Champs obligatoires manquants'); return $this->redirectToRoute('admin_dashboard'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))      { $this->addFlash('error', '❌ Email invalide'); return $this->redirectToRoute('admin_dashboard'); }
        if (strlen($mdp) < 6)                                { $this->addFlash('error', '❌ Mot de passe min. 6 caractères'); return $this->redirectToRoute('admin_dashboard'); }
        if (empty($roles))                                   { $this->addFlash('error', '❌ Sélectionnez un rôle'); return $this->redirectToRoute('admin_dashboard'); }
        if ($em->getRepository(Utilisateur::class)->findOneBy(['email' => $email])) { $this->addFlash('error', '❌ Email déjà utilisé'); return $this->redirectToRoute('admin_dashboard'); }
        try {
            $u = new Utilisateur();
            $u->setNom($nom); $u->setPrenom($prenom); $u->setEmail($email);
            $u->setTelephone($tel ?: null); $u->setStatut($statut); $u->setRoles($roles);
            $u->setMotDePasse($this->passwordHasher->hashPassword($u, $mdp));
            $em->persist($u); $em->flush();
            $this->addFlash('success', '✅ Utilisateur '.$u->getFullName().' créé !');
        } catch (\Exception $e) { $this->addFlash('error', '❌ '.$e->getMessage()); }
        return $this->redirectToRoute('admin_dashboard');
    }

    // MODIFIER
    #[Route('/security/user/{id}/update', name: 'admin_security_user_update', methods: ['POST'])]
    public function updateUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('user_edit_'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token invalide'); return $this->redirectToRoute('admin_dashboard');
        }
        $u = $em->getRepository(Utilisateur::class)->find($id);
        if (!$u) { $this->addFlash('error', '❌ Utilisateur introuvable'); return $this->redirectToRoute('admin_dashboard'); }
        $nom = trim($request->request->get('nom', '')); $prenom = trim($request->request->get('prenom', ''));
        $email = trim($request->request->get('email', '')); $tel = trim($request->request->get('telephone', ''));
        $roles = $request->request->all('roles');
        if (empty($nom) || empty($prenom) || empty($email)) { $this->addFlash('error', '❌ Champs obligatoires manquants'); return $this->redirectToRoute('admin_dashboard'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))      { $this->addFlash('error', '❌ Email invalide'); return $this->redirectToRoute('admin_dashboard'); }
        if (empty($roles))                                   { $this->addFlash('error', '❌ Sélectionnez un rôle'); return $this->redirectToRoute('admin_dashboard'); }
        $ex = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        if ($ex && $ex->getId() !== $u->getId()) { $this->addFlash('error', '❌ Email déjà utilisé'); return $this->redirectToRoute('admin_dashboard'); }
        try {
            $u->setNom($nom); $u->setPrenom($prenom); $u->setEmail($email);
            $u->setTelephone($tel ?: null);
            // ⛔ Statut non modifiable manuellement — calculé via last_login
            $u->setRoles($roles);
            $plain = $request->request->get('mot_de_passe', '');
            if (!empty($plain)) {
                if (strlen($plain) < 6) { $this->addFlash('error', '❌ Mot de passe min. 6 caractères'); return $this->redirectToRoute('admin_dashboard'); }
                $u->setMotDePasse($this->passwordHasher->hashPassword($u, $plain));
            }
            $em->flush();
            $this->addFlash('success', '✅ Utilisateur '.$u->getFullName().' modifié !');
        } catch (\Exception $e) { $this->addFlash('error', '❌ '.$e->getMessage()); }
        return $this->redirectToRoute('admin_dashboard');
    }

    // SUPPRIMER
    #[Route('/security/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('user_delete_'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token invalide'); return $this->redirectToRoute('admin_dashboard');
        }
        $u = $em->getRepository(Utilisateur::class)->find($id);
        if (!$u) { $this->addFlash('error', '❌ Introuvable'); return $this->redirectToRoute('admin_dashboard'); }
        /** @var Utilisateur $me */ $me = $this->getUser();
        if ($u->getId() === $me->getId()) { $this->addFlash('error', '❌ Vous ne pouvez pas supprimer votre propre compte'); return $this->redirectToRoute('admin_dashboard'); }
        try {
            $name = $u->getFullName(); $em->remove($u); $em->flush();
            $this->addFlash('success', '✅ Utilisateur '.$name.' supprimé !');
        } catch (\Exception $e) { $this->addFlash('error', '❌ '.$e->getMessage()); }
        return $this->redirectToRoute('admin_dashboard');
    }

    // PROFIL ADMIN
    #[Route('/profile/edit', name: 'admin_profile_edit', methods: ['POST'])]
    public function editProfile(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_profile_edit', $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token invalide'); return $this->redirectToRoute('admin_dashboard');
        }
        /** @var Utilisateur $u */ $u = $this->getUser();
        $nom = trim($request->request->get('nom', '')); $prenom = trim($request->request->get('prenom', ''));
        $email = trim($request->request->get('email', '')); $tel = trim($request->request->get('telephone', ''));
        if (empty($nom) || empty($prenom) || empty($email)) { $this->addFlash('error', '❌ Champs obligatoires manquants'); return $this->redirectToRoute('admin_dashboard'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->addFlash('error', '❌ Email invalide'); return $this->redirectToRoute('admin_dashboard'); }
        $ex = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        if ($ex && $ex->getId() !== $u->getId()) { $this->addFlash('error', '❌ Email déjà utilisé'); return $this->redirectToRoute('admin_dashboard'); }
        try {
            $u->setNom($nom); $u->setPrenom($prenom); $u->setEmail($email); $u->setTelephone($tel ?: null);
            $em->flush(); $this->addFlash('success', '✅ Profil modifié !');
        } catch (\Exception $e) { $this->addFlash('error', '❌ '.$e->getMessage()); }
        return $this->redirectToRoute('admin_dashboard');
    }

    // EXPORT CSV
    #[Route('/users/export-csv', name: 'admin_users_export_csv')]
    public function exportCsv(UtilisateurRepository $repo): Response
    {
        $users = $repo->findAll();
        $response = new StreamedResponse(function () use ($users) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['ID','Prénom','Nom','Email','Téléphone','Rôle','Statut','Inscription','Dernière connexion'], ';');
            foreach ($users as $u) {
                $r = $u->getRoles();
                $roleText = in_array('ROLE_ADMIN',$r) ? 'Administrateur' : (in_array('ROLE_PROFESSIONNEL',$r) ? 'Professionnel' : 'Client');
                fputcsv($h, [
                    $u->getId(), $u->getPrenom(), $u->getNom(), $u->getEmail(),
                    $u->getTelephone() ?? '', $roleText, $u->getStatut(),
                    $u->getDateCreation()?->format('d/m/Y H:i') ?? '',
                    $u->getLastLogin()?->format('d/m/Y H:i') ?? 'Jamais connecté',
                ], ';');
            }
            fclose($h);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="utilisateurs_'.date('Y-m-d_H-i').'.csv"');
        return $response;
    }

    // 🤖 API IA — INACTIFS
    #[Route('/users/inactifs', name: 'admin_users_inactifs_api', methods: ['GET'])]
    public function getInactifsApi(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $seuil  = max(1, (int) $request->query->get('jours', 7));
        $depuis = new \DateTime("-{$seuil} days");
        $qb     = $em->createQueryBuilder();
        $list   = $qb->select('u')->from(Utilisateur::class, 'u')
            ->where($qb->expr()->orX(
                $qb->expr()->isNull('u.lastLogin'),
                $qb->expr()->lt('u.lastLogin', ':depuis')
            ))
            ->setParameter('depuis', $depuis)
            ->orderBy('u.lastLogin', 'ASC')
            ->getQuery()->getResult();
        $now  = new \DateTime();
        $data = array_map(fn(Utilisateur $u): array => [
            'id'            => $u->getId(),
            'nom'           => $u->getFullName(),
            'email'         => $u->getEmail(),
            'roles'         => $u->getRoles(),
            'statut'        => $u->getStatut(),
            'last_login'    => $u->getLastLogin()?->format('d/m/Y H:i'),
            'jours_inactif' => $u->getLastLogin() ? (int)$now->diff($u->getLastLogin())->days : null,
        ], $list);
        return new JsonResponse(['total_inactifs' => count($data), 'seuil_jours' => $seuil, 'utilisateurs' => $data]);
    }
}