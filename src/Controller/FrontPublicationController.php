<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PublicationRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Publication;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Form\PublicationType;

final class FrontPublicationController extends AbstractController
{   
    #[Route('/dashboard/forum', name: 'app_dashboard_forum')] 
    #[Route('/front/publication', name: 'app_front_publication')]
    public function index(PublicationRepository $publicationRepository, Request $request): Response
    {
        $search = $request->query->get('q');
        $tag = $request->query->get('tag');

        if ($tag) {
            // Recherche contextuelle par sujet (Anxiété, Stress, etc.)
            $publications = $publicationRepository->findByTopic($tag);
        } elseif ($search) {
            // Recherche par mot-clé global dans titre, contenu et commentaires
            $publications = $publicationRepository->createQueryBuilder('p')
                ->leftJoin('p.pubCom', 'c')
                ->where('p.titre LIKE :search')
                ->orWhere('p.contenu LIKE :search')
                ->orWhere('c.contenu LIKE :search')
                ->andWhere('p.isDeleted = :false')
                ->setParameter('search', '%' . $search . '%')
                ->setParameter('false', false)
                ->orderBy('p.datePublication', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            // Toutes les publications non supprimées
            $publications = $publicationRepository->findBy(['isDeleted' => false], ['datePublication' => 'DESC']);
        }

        return $this->render('front_publication/index.html.twig', [
            'publications' => $publications,
            'search' => $search,
            'currentTag' => $tag,
        ]);
    }

    #[Route(
        '/front/publication/{id}',
        name: 'app_front_publication_show',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function show(Publication $publication): Response
    {
        return $this->render('front_publication/show.html.twig', [
            'publication' => $publication,
        ]);
    }


    #[Route('/front/publication/new', name: 'app_front_publication_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $publication = new Publication();
        
        // Simuler utilisateur connecté (id=1) - INDISPENSABLE avant isValid()
        $user = $entityManager->getRepository(Utilisateur::class)->find(1);
        $publication->setUser($user);

        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setDatePublication(new \DateTime());

            // IMAGE Handling
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
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }
            }

            $entityManager->persist($publication);
            $entityManager->flush();

            return $this->redirectToRoute('app_front_publication');
        }

        return $this->render('front_publication/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/front/publication/{id}/delete', name: 'app_front_publication_delete', methods: ['POST'])]

public function delete(?Publication $publication, EntityManagerInterface $em): Response

{

    // Si l'ID dans l'URL ne correspond à aucune publication

    if (!$publication) {

        $this->addFlash('error', 'Désolé, cette publication n\'existe pas ou a déjà été supprimée.');

        return $this->redirectToRoute('app_front_publication');

    }
    $fakeUser = $em->getRepository(Utilisateur::class)->find(1);
    if ($publication->getUser() !== $fakeUser) {

        $this->addFlash('error', 'Action non autorisée.');

        return $this->redirectToRoute('app_front_publication');
    }
    $em->remove($publication);

    $em->flush();

    $this->addFlash('success', 'Publication supprimée !');

    return $this->redirectToRoute('app_front_publication');

}
    #[Route('/front/publication/{id}/edit', name: 'app_front_publication_edit', methods: ['GET', 'POST'])]
    public function edit(?Publication $publication, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    if (!$publication) {
        $this->addFlash('error', 'Cette publication n\'existe pas.');
        return $this->redirectToRoute('app_front_publication');
    }

    $fakeUser = $em->getRepository(Utilisateur::class)->find(1);

    if ($publication->getUser() !== $fakeUser) {
        throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cette publication !");
    }

    $form = $this->createForm(PublicationType::class, $publication);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $newFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)) 
                           . '-' . uniqid() . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move($this->getParameter('uploads_directory'), $newFilename);
                $publication->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur upload image');
            }
        }

        $em->flush();
        $this->addFlash('success', 'Publication modifiée !');
        return $this->redirectToRoute('app_front_publication');
    }

    return $this->render('front_publication/edit.html.twig', [
        'publication' => $publication,
        'form' => $form->createView(),
    ]);
}
#[Route('/front/publication/{id}/like', name: 'app_front_publication_like', methods: ['POST'])]
    public function like(Publication $publication, Request $request, EntityManagerInterface $em): Response
{
    // Exemple simple : incrémenter le compteur
    $publication->setLikesCount($publication->getLikesCount() + 1);

    // Simulation de l'utilisateur qui like (ex: id=2)
    $liker = $em->getRepository(Utilisateur::class)->find(2);
    if (!$liker) $liker = $em->getRepository(Utilisateur::class)->find(1);

    // Notification pour l'auteur de la publication
    if ($publication->getUser()) {
        $publication->setNotificationMessage("Votre publication a reçu un nouveau Like !");
        $publication->setNotificationRead(false);
        $publication->setNotificationDate(new \DateTime());
    }

    $em->flush();

    // Redirection vers la page précédente (détail ou liste)
    return $this->redirect($request->headers->get('referer'));
}

#[Route('/front/publication/{id}/dislike', name: 'app_front_publication_dislike', methods: ['POST'])]
    public function dislike(Publication $publication, Request $request, EntityManagerInterface $em): Response
{
    $publication->setDislikesCount($publication->getDislikesCount() + 1);

    // Simulation de l'utilisateur qui dislike (ex: id=2)
    $disliker = $em->getRepository(Utilisateur::class)->find(2);
    if (!$disliker) $disliker = $em->getRepository(Utilisateur::class)->find(1);

    // Notification pour l'auteur de la publication
    if ($publication->getUser()) {
        $publication->setNotificationMessage("Votre publication a reçu un nouveau Dislike.");
        $publication->setNotificationRead(false);
        $publication->setNotificationDate(new \DateTime());
    }

    $em->flush();

    return $this->redirect($request->headers->get('referer'));
}

    public function badge(PublicationRepository $repository): Response
    {
        // Simuler utilisateur connecté (id=1)
        $unread = $repository->findUnreadNotifications(1);
        
        return $this->render('components/_notification_badge.html.twig', [
            'count' => count($unread),
            'notifications' => $unread
        ]);
    }

    #[Route('/notification/mark-as-read', name: 'app_notification_mark_as_read', methods: ['POST'])]
    public function markAsRead(PublicationRepository $repository, EntityManagerInterface $em): Response
    {
        $unread = $repository->findUnreadNotifications(1);
        foreach ($unread as $publication) {
            $publication->setNotificationRead(true);
        }
        $em->flush();
        
        return $this->json(['success' => true]);
    }
}
