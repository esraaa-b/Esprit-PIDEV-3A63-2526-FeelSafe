<?php

namespace App\Controller\Admin;

use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Utilisateur;

#[Route('/admin/forum')]
class ForumController extends AbstractController
{
    #[Route('', name: 'admin_forum_index', methods: ['GET'])]
    public function index(Request $request, PublicationRepository $publicationRepository): Response
    {
        $searchId = $request->query->get('search_id');
        $searchTitre = $request->query->get('search_titre');
        $sortBy = $request->query->get('sort_by', 'datePublication');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        $queryBuilder = $publicationRepository->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if ($searchId) {
            $queryBuilder->andWhere('p.id = :id')->setParameter('id', $searchId);
        }
        if ($searchTitre) {
            $queryBuilder->andWhere('p.titre LIKE :titre')->setParameter('titre', '%' . $searchTitre . '%');
        }

        $validSortFields = ['id', 'titre', 'datePublication'];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'datePublication';
        }

        $validSortOrders = ['ASC', 'DESC'];
        if (!in_array(strtoupper($sortOrder), $validSortOrders)) {
            $sortOrder = 'DESC';
        }

        $queryBuilder->orderBy('p.' . $sortBy, $sortOrder);

        return $this->render('admin/forum/index.html.twig', [
            'publications' => $queryBuilder->getQuery()->getResult(),
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'admin_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $publication = new Publication();

        $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['role' => 'ROLE_ADMIN']);
        if (!$user) {
            $user = $entityManager->getRepository(Utilisateur::class)->findOneBy([], ['id' => 'ASC']);
        }

        if (!$user) {
            $this->addFlash('error', 'Erreur critique : Aucun utilisateur trouvé dans la base de données.');
            return $this->redirectToRoute('admin_forum_index');
        }

        $publication->setUser($user);

        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    $publication->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Problème lors de l\'enregistrement de l\'image.');
                }
            }

            $entityManager->persist($publication);
            $entityManager->flush();

            $this->addFlash('success', 'Publication créée avec succès !');
            return $this->redirectToRoute('admin_forum_show', ['id' => $publication->getId()]);
        }

        return $this->render('admin/forum/new.html.twig', [
            'publication' => $publication,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_forum_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Publication $publication, EntityManagerInterface $entityManager): Response
    {
        $commentaires = $entityManager->getRepository(Commentaire::class)
            ->findBy(['publication' => $publication], ['dateCommentaire' => 'DESC']);

        return $this->render('admin/forum/show.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Publication $publication, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                if ($publication->getImage()) {
                    $oldImage = $this->getParameter('uploads_directory') . '/' . $publication->getImage();
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    $publication->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Problème lors de l\'enregistrement de l\'image.');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Publication modifiée avec succès !');

            return $this->redirectToRoute('admin_forum_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/forum/edit.html.twig', [
            'publication' => $publication,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_forum_delete', methods: ['POST'])]
    public function delete(Request $request, Publication $publication, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $publication->getId(), $request->request->get('_token'))) {
            if ($publication->getImage()) {
                $imagePath = $this->getParameter('uploads_directory') . '/' . $publication->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Publication supprimée avec succès !');
        }

        return $this->redirectToRoute('admin_forum_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/stats', name: 'admin_forum_stats', methods: ['GET'])]
    public function stats(EntityManagerInterface $em): Response
    {
        // ===== CORRECTION : Utiliser des COUNT au lieu de findAll() =====
        
        // Compter les publications et commentaires sans les charger
        $totalPublications = $em->createQuery('SELECT COUNT(p) FROM App\Entity\Publication p')
            ->getSingleScalarResult();
        
        $totalCommentaires = $em->createQuery('SELECT COUNT(c) FROM App\Entity\Commentaire c')
            ->getSingleScalarResult();
        
        // Récupérer les statistiques mensuelles avec des requêtes GROUP BY
        $pubStats = $em->createQuery('
            SELECT MONTH(p.datePublication) as month, COUNT(p) as count 
            FROM App\Entity\Publication p 
            GROUP BY month
        ')->getResult();
        
        $comStats = $em->createQuery('
            SELECT MONTH(c.dateCommentaire) as month, COUNT(c) as count 
            FROM App\Entity\Commentaire c 
            GROUP BY month
        ')->getResult();
        
        // Initialiser les compteurs
        $pubCounts = array_fill(0, 12, 0);
        $comCounts = array_fill(0, 12, 0);
        
        // Remplir avec les résultats
        foreach ($pubStats as $stat) {
            $month = (int) $stat['month'] - 1;
            $pubCounts[$month] = (int) $stat['count'];
        }
        
        foreach ($comStats as $stat) {
            $month = (int) $stat['month'] - 1;
            $comCounts[$month] = (int) $stat['count'];
        }
        
        // Moyenne des commentaires par publication
        $moyenneCommentaires = $totalPublications > 0 
            ? round($totalCommentaires / $totalPublications, 1) 
            : 0;

        // Trouver la publication avec le plus de commentaires (sans tout charger)
        $topPublication = $em->createQuery('
            SELECT p, COUNT(c) as HIDDEN commentCount 
            FROM App\Entity\Publication p
            LEFT JOIN p.pubCom c
            GROUP BY p
            ORDER BY commentCount DESC
        ')->setMaxResults(1)->getOneOrNullResult();
        
        $maxCommentaires = $topPublication 
            ? count($topPublication->getPubCom()) 
            : 0;

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date('F', mktime(0, 0, 0, $i, 1));
        }

        return $this->render('admin/forum/stats.html.twig', [
            'months' => $months,
            'pubCounts' => $pubCounts,
            'comCounts' => $comCounts,
            'totalPublications' => $totalPublications,
            'totalCommentaires' => $totalCommentaires,
            'moyenneCommentaires' => $moyenneCommentaires,
            'topPublication' => $topPublication,
            'maxCommentaires' => $maxCommentaires,
        ]);
    }

    #[Route('/commentaire/{id}/delete', name: 'admin_forum_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->request->get('_token'))) {
            $publicationId = $commentaire->getPublication()->getId();
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé avec succès !');

            return $this->redirectToRoute('admin_forum_show', ['id' => $publicationId]);
        }

        return $this->redirectToRoute('admin_forum_index');
    }
}

