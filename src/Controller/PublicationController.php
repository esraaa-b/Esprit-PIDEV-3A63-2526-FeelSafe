<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Form\PublicationType;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Utilisateur;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;

#[Route('/publication')]
final class PublicationController extends AbstractController
{
    #[Route(name: 'app_publication_index', methods: ['GET'])]
    public function index(Request $request, PublicationRepository $publicationRepository): Response
    {
        $searchId = $request->query->get('search_id');
        $searchTitre = $request->query->get('search_titre');
        $sortBy = $request->query->get('sort_by', 'datePublication');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        $queryBuilder = $publicationRepository->createQueryBuilder('p')
        ->where('p.isDeleted = :isDeleted')
        ->setParameter('isDeleted', false);

        if ($searchId) {
            $queryBuilder->andWhere('p.id = :id')->setParameter('id', $searchId);
        }
        if ($searchTitre) {
            $queryBuilder->andWhere('p.titre LIKE :titre')->setParameter('titre', '%' . $searchTitre . '%');
        }

        $validSortFields = ['id', 'titre', 'datePublication'];
        if (!in_array($sortBy, $validSortFields)) $sortBy = 'datePublication';
       
        $validSortOrders = ['ASC', 'DESC'];
        if (!in_array(strtoupper($sortOrder), $validSortOrders)) $sortOrder = 'DESC';

        $queryBuilder->orderBy('p.' . $sortBy, $sortOrder);

        return $this->render('publication/index.html.twig', [
            'publications' => $queryBuilder->getQuery()->getResult(),
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);
    }

    #[Route('/export-pdf', name: 'app_publication_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, PublicationRepository $publicationRepository, DompdfFactoryInterface $dompdfFactory): Response
    {
        $searchId = $request->query->get('search_id');
        $searchTitre = $request->query->get('search_titre');
        $sortBy = $request->query->get('sort_by', 'datePublication');
        $sortOrder = $request->query->get('sort_order', 'DESC');

        $queryBuilder = $publicationRepository->createQueryBuilder('p')
            ->where('p.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false);

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
        $publications = $queryBuilder->getQuery()->getResult();

        $html = $this->renderView('publication/pdf_export.html.twig', [
            'publications' => $publications,
            'generatedAt' => new \DateTime(),
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);

        $dompdf = $dompdfFactory->create();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'publications-' . (new \DateTime())->format('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/new', name: 'app_publication_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $publication = new Publication();
       
        // 1. Pré-remplissage des données obligatoires non présentes dans le formulaire
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }
       
        $publication->setUser($user);
        $form = $this->createForm(PublicationType::class, $publication, [
            'include_pinned' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
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
            return $this->redirectToRoute('app_publication_show', ['id' => $publication->getId()]);
        }

        return $this->render('publication/new.html.twig', [
            'publication' => $publication,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_publication_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, CommentaireRepository $commentaireRepo, PublicationRepository $publicationRepository): Response
    {
        $publication = $publicationRepository->find($id);
        if (!$publication) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publication_index');
        }
        $rootComments = $commentaireRepo->findRootByPublication($publication);

        return $this->render('publication/show.html.twig', [
            'publication' => $publication,
            'rootComments' => $rootComments,
        ]);
    }

    #[Route('/commentaire/{id}/delete', name: 'app_publication_commentaire_delete', methods: ['POST'])]
    public function deleteComment(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager
    ): Response {
        $publicationId = $commentaire->getPublication()->getId();

        if ($this->isCsrfTokenValid('delete_comment' . $commentaire->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé par l\'administrateur.');
        }

        return $this->redirectToRoute('app_publication_show', ['id' => $publicationId]);
    }
   
    #[Route('/{id}/edit', name: 'app_publication_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $id,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        PublicationRepository $publicationRepository
    ): Response
    {
        $publication = $publicationRepository->find($id);
        if (!$publication) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publication_index');
        }
        $form = $this->createForm(PublicationType::class, $publication, [
            'include_pinned' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'image uploadée
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
                } catch (FileException $e) {
                    // gérer l'erreur si besoin
                }

                $publication->setImage($newFilename);
            }

            // Notification à l'auteur
            if ($publication->getUser()) {  // toujours true car non-nullable
            $publication->setNotificationMessage("Votre publication '" . $publication->getTitre() . "' a été modifiée par un administrateur.");
            $publication->setNotificationRead(false);
            $publication->setNotificationDate(new \DateTimeImmutable());
        }

        $entityManager->flush();

            return $this->redirectToRoute('app_publication_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('publication/edit.html.twig', [
            'publication' => $publication,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_publication_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $id,
        EntityManagerInterface $entityManager,
        PublicationRepository $publicationRepository
    ): Response
    {
        $publication = $publicationRepository->find($id);
        if (!$publication) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publication_index');
        }
        if ($this->isCsrfTokenValid('delete' . $publication->getId(), $request->request->get('_token'))) {
            // Suppression réelle de la base de données
            $entityManager->remove($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Publication supprimée définitivement.');
        }

        return $this->redirectToRoute('app_publication_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/stats', name: 'app_publication_stats', methods: ['GET'])]
    public function stats(EntityManagerInterface $em): Response
    {
        // Récupérer toutes les publications
        $publications = $em->getRepository(Publication::class)->findAll();

        // Récupérer tous les commentaires
        $commentaires = $em->getRepository(Commentaire::class)->findAll();

        $pubCounts = array_fill(0, 12, 0); // Index 0-11
        $comCounts = array_fill(0, 12, 0); // Index 0-11
        $reactCounts = array_fill(0, 12, 0); // Index 0-11

        // Compter les publications et leurs réactions par mois
        foreach ($publications as $p) {
            if ($p->getDatePublication()) {
                $month = (int)$p->getDatePublication()->format('n') - 1; // 0-11
                $pubCounts[$month]++;
                $reactCounts[$month] += ($p->getLikesCount() + $p->getDislikesCount());
            }
        }

        // Compter les commentaires et leurs réactions par mois
        foreach ($commentaires as $c) {
            if ($c->getDateCommentaire()) {
                $month = (int)$c->getDateCommentaire()->format('n') - 1; // 0-11
                $comCounts[$month]++;
                $reactCounts[$month] += ($c->getLikesCount() + $c->getDislikesCount());
            }
        }

        // Préparer les labels des mois
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date('F', mktime(0, 0, 0, $i, 1));
        }

        // Données pour les graphiques par catégorie
        $categories = ['Anxiété', 'Dépression', 'Stress', 'Angoisse', 'Addiction', 'Solitude', 'Autre'];
        $catLikes = array_fill_keys($categories, 0);
        $catDislikes = array_fill_keys($categories, 0);

        // KPIs globaux
        $totalLikes = 0;
        $totalDislikes = 0;
        $totalComments = count($commentaires);
        $totalPubs = count($publications);

        foreach ($publications as $p) {
            $cat = $p->getCategorie();
            if ($cat && isset($catLikes[$cat])) {
                $catLikes[$cat] += $p->getLikesCount();
                $catDislikes[$cat] += $p->getDislikesCount();
            } else {
                $catLikes['Autre'] += $p->getLikesCount();
                $catDislikes['Autre'] += $p->getDislikesCount();
            }
            $totalLikes += $p->getLikesCount();
            $totalDislikes += $p->getDislikesCount();
        }

        // Top 5 Publications par réactions
        $topPublications = $publications;
        usort($topPublications, function($a, $b) {
            return ($b->getLikesCount() + $b->getDislikesCount()) <=> ($a->getLikesCount() + $a->getDislikesCount());
        });
        $topPublications = array_slice($topPublications, 0, 5);

        return $this->render('publication/stats.html.twig', [
            'months' => $months,
            'pubCounts' => $pubCounts,
            'comCounts' => $comCounts,
            'reactCounts' => $reactCounts,
            'catLabels' => array_keys($catLikes),
            'catLikes' => array_values($catLikes),
            'catDislikes' => array_values($catDislikes),
            'totalLikes' => $totalLikes,
            'totalDislikes' => $totalDislikes,
            'totalComments' => $totalComments,
            'totalPubs' => $totalPubs,
            'topPublications' => $topPublications,
        ]);
    }
}