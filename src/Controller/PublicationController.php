<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;

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

        $queryBuilder = $publicationRepository->createQueryBuilder('p');

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

    #[Route('/new', name: 'app_publication_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $publication = new Publication();
        
        // 1. Pré-remplissage des données obligatoires non présentes dans le formulaire
        $user = $entityManager->getRepository(Utilisateur::class)->find(1);
        if (!$user) {
            $this->addFlash('error', 'Erreur critique : L\'utilisateur par défaut (ID 1) n est pas dans la base de données.');
            return $this->redirectToRoute('app_publication_index');
        }
        
        $publication->setUser($user);
        $publication->setDatePublication(new \DateTime());

        $form = $this->createForm(PublicationType::class, $publication);
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
    public function show(Publication $publication): Response
    {
        return $this->render('publication/show.html.twig', [
            'publication' => $publication,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_publication_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Publication $publication, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PublicationType::class, $publication);
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

            $entityManager->flush();

            return $this->redirectToRoute('app_publication_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('publication/edit.html.twig', [
            'publication' => $publication,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_publication_delete', methods: ['POST'])]
    public function delete(Request $request, Publication $publication, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $publication->getId(), $request->request->get('_token'))) {
            $entityManager->remove($publication);
            $entityManager->flush();
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

        // Initialiser le comptage des publications par mois
        $pubCounts = array_fill(0, 12, 0); // Index 0-11
        $comCounts = array_fill(0, 12, 0); // Index 0-11

        // Compter les publications par mois
        foreach ($publications as $p) {
            if ($p->getDatePublication()) {
                $month = (int)$p->getDatePublication()->format('n') - 1; // 0-11
                $pubCounts[$month]++;
            }
        }

        // Compter les commentaires par mois
        foreach ($commentaires as $c) {
            if ($c->getDateCommentaire()) {
                $month = (int)$c->getDateCommentaire()->format('n') - 1; // 0-11
                $comCounts[$month]++;
            }
        }

        // Préparer les labels des mois
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date('F', mktime(0, 0, 0, $i, 1));
        }

        return $this->render('publication/stats.html.twig', [
            'months' => $months,
            'pubCounts' => $pubCounts, // Publications par mois
            'comCounts' => $comCounts, // Commentaires par mois
        ]);
    }
}
