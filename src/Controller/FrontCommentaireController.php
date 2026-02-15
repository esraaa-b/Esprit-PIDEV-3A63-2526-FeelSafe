<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Commentaire;
use App\Entity\CommentaireLike;
use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Repository\CommentaireLikeRepository;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/front/commentaire')]
final class FrontCommentaireController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_front_commentaire_new', methods: ['POST'])]
    public function new(Publication $publication, Request $request, EntityManagerInterface $em): Response
    {
        $contenu = $request->request->get('contenu');
        $gifUrl = $request->request->get('gif_url');

        if ($contenu || $gifUrl) {
            $commentaire = new Commentaire();
            $commentaire->setContenu($contenu ?? '');
            $commentaire->setGifUrl($gifUrl);
            $commentaire->setDateCommentaire(new \DateTime());
            $commentaire->setPublication($publication);

            // Simuler utilisateur connecté (id=1)
            $user = $em->getRepository(Utilisateur::class)->find(1); 
            
            $commentaire->setUser($user);

            $em->persist($commentaire);

            // Notification pour l'auteur de la publication via Publication
            if ($publication->getUser()) {
                $publication->setNotificationMessage("Nouveau commentaire de " . $user->getNom() . " sur votre publication.");
                $publication->setNotificationRead(false);
                $publication->setNotificationDate(new \DateTime());
            }

            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté !');
        }

        return $this->redirectToRoute('app_front_publication_show', ['id' => $publication->getId()]);
    }

    #[Route('/reply/{id}', name: 'app_front_commentaire_reply', methods: ['POST'])]
    public function reply(Commentaire $parentComment, Request $request, EntityManagerInterface $em): Response
    {
        $contenu = $request->request->get('contenu');
        $gifUrl = $request->request->get('gif_url');

        if ($contenu || $gifUrl) {
            $reply = new Commentaire();
            $reply->setContenu($contenu ?? '');
            $reply->setGifUrl($gifUrl);
            $reply->setDateCommentaire(new \DateTime());
            $reply->setPublication($parentComment->getPublication());
            $reply->setParent($parentComment);

            // Simuler utilisateur connecté (id=1)
            $user = $em->getRepository(Utilisateur::class)->find(1);
            $reply->setUser($user);

            $em->persist($reply);

            // Notification pour l'auteur du commentaire parent
            $publication = $parentComment->getPublication();
            if ($parentComment->getUser()) {
                $publication->setNotificationMessage(
                    $user->getNom() . " a répondu à votre commentaire."
                );
                $publication->setNotificationRead(false);
                $publication->setNotificationDate(new \DateTime());
            }

            $em->flush();

            $this->addFlash('success', 'Réponse ajoutée !');
        }

        return $this->redirectToRoute('app_front_publication_show', [
            'id' => $parentComment->getPublication()->getId()
        ]);
    }

    #[Route('/{id}/like', name: 'app_front_commentaire_like', methods: ['POST'])]
    public function like(
        Commentaire $commentaire,
        EntityManagerInterface $em,
        CommentaireLikeRepository $likeRepo
    ): Response {
        // Simuler utilisateur connecté (id=1)
        $user = $em->getRepository(Utilisateur::class)->find(1);

        $existingVote = $likeRepo->findByUserAndCommentaire($user->getId(), $commentaire->getId());

        if ($existingVote) {
            if ($existingVote->getType() === 'like') {
                // Already liked → remove vote
                $em->remove($existingVote);
                $commentaire->setLikesCount($commentaire->getLikesCount() - 1);
            } else {
                // Was dislike → switch to like
                $existingVote->setType('like');
                $commentaire->setDislikesCount($commentaire->getDislikesCount() - 1);
                $commentaire->setLikesCount($commentaire->getLikesCount() + 1);
            }
        } else {
            // New like
            $vote = new CommentaireLike();
            $vote->setCommentaire($commentaire);
            $vote->setUser($user);
            $vote->setType('like');
            $em->persist($vote);
            $commentaire->setLikesCount($commentaire->getLikesCount() + 1);
        }

        $em->flush();

        return $this->redirectToRoute('app_front_publication_show', [
            'id' => $commentaire->getPublication()->getId()
        ]);
    }

    #[Route('/{id}/dislike', name: 'app_front_commentaire_dislike', methods: ['POST'])]
    public function dislike(
        Commentaire $commentaire,
        EntityManagerInterface $em,
        CommentaireLikeRepository $likeRepo
    ): Response {
        // Simuler utilisateur connecté (id=1)
        $user = $em->getRepository(Utilisateur::class)->find(1);

        $existingVote = $likeRepo->findByUserAndCommentaire($user->getId(), $commentaire->getId());

        if ($existingVote) {
            if ($existingVote->getType() === 'dislike') {
                // Already disliked → remove vote
                $em->remove($existingVote);
                $commentaire->setDislikesCount($commentaire->getDislikesCount() - 1);
            } else {
                // Was like → switch to dislike
                $existingVote->setType('dislike');
                $commentaire->setLikesCount($commentaire->getLikesCount() - 1);
                $commentaire->setDislikesCount($commentaire->getDislikesCount() + 1);
            }
        } else {
            // New dislike
            $vote = new CommentaireLike();
            $vote->setCommentaire($commentaire);
            $vote->setUser($user);
            $vote->setType('dislike');
            $em->persist($vote);
            $commentaire->setDislikesCount($commentaire->getDislikesCount() + 1);
        }

        $em->flush();

        return $this->redirectToRoute('app_front_publication_show', [
            'id' => $commentaire->getPublication()->getId()
        ]);
    }

    #[Route('/{id}/delete', name: 'app_front_commentaire_delete', methods: ['POST'])]
    public function delete(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        // Simuler utilisateur connecté (id=1)
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);

        $isCommentAuthor = ($commentaire->getUser() === $fakeUser);
        $isPublicationAuthor = ($commentaire->getPublication()->getUser() === $fakeUser);

        if (!$isCommentAuthor && !$isPublicationAuthor) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer ce commentaire !");
        }

        $publicationId = $commentaire->getPublication()->getId();

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé !');

        return $this->redirectToRoute('app_front_publication_show', ['id' => $publicationId]);
    }

    #[Route('/{id}/edit', name: 'app_front_commentaire_edit', methods: ['GET', 'POST'])]
    public function edit(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        // Simuler utilisateur connecté (id=1)
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);

        if ($commentaire->getUser() !== $fakeUser) {
            throw $this->createAccessDeniedException("Seul l'auteur peut modifier son commentaire !");
        }

        // Si le formulaire est soumis
        $contenu = $request->request->get('contenu');
        $gifUrl = $request->request->get('gif_url');
        
        if ($contenu !== null || $gifUrl !== null) {
            $commentaire->setContenu($contenu ?? '');
            $commentaire->setGifUrl($gifUrl);
            $commentaire->setDateCommentaire(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Commentaire mis à jour !');

            return $this->redirectToRoute('app_front_publication_show', [
                'id' => $commentaire->getPublication()->getId()
            ]);
        }

        // Affichage du formulaire simple
        return $this->render('front_commentaire/edit.html.twig', [
            'commentaire' => $commentaire
        ]);
    }
}
