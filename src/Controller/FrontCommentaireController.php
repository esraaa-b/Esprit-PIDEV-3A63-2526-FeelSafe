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
            $commentaire->setDateCommentaire(new \DateTimeImmutable());
            $commentaire->setPublication($publication);

            $user = $this->getUser();
            if (!$user instanceof Utilisateur) {
                $this->addFlash('error', 'Vous devez être connecté pour commenter.');
                return $this->redirectToRoute('app_login');
            }
            $commentaire->setUser($user);

            $em->persist($commentaire);

            // Ne notifier que si l'auteur du commentaire n'est pas l'auteur de la publication
            if ($publication->getUser() !== $user) {
                $publication->setNotificationMessage("Nouveau commentaire de " . $user->getNom() . " sur votre publication.");
                $publication->setNotificationRead(false);
                $publication->setNotificationDate(new \DateTimeImmutable());
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
            $reply->setDateCommentaire(new \DateTimeImmutable());
            $reply->setPublication($parentComment->getPublication());
            $reply->setParent($parentComment);

            $user = $this->getUser();
            if (!$user instanceof Utilisateur) {
                return $this->json(['error' => 'Non connecté'], 403);
            }
            $reply->setUser($user);

            $em->persist($reply);

            $publication = $parentComment->getPublication();
            $publication->setNotificationMessage($user->getNom() . " a répondu à votre commentaire.");
            $publication->setNotificationRead(false);
            $publication->setNotificationDate(new \DateTimeImmutable());

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
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            $this->addFlash('error', 'Vous devez être connecté pour voter.');
            return $this->redirectToRoute('app_login');
        }
        try {
            $commentaireId = $commentaire->getId();
            $publicationId = $commentaire->getPublication()->getId();

            // Get current vote (safe even with legacy duplicate rows)
            $existingVote = $likeRepo->findByUserAndCommentaire($user->getId(), $commentaireId);
            $currentType = $existingVote ? $existingVote->getType() : null;

            // Delete ALL existing votes (cleans up legacy duplicates)
            $likeRepo->deleteAllForUserAndCommentaire($user->getId(), $commentaireId);
            $em->clear();

            // Re-fetch detached entities
            $commentaire = $em->find(Commentaire::class, $commentaireId);

            if ($currentType !== 'like') {
                $vote = new CommentaireLike();
                $vote->setCommentaire($commentaire);
                $vote->setUser($em->getReference(Utilisateur::class, $user->getId()));
                $vote->setType('like');
                $em->persist($vote);
            }
            $em->flush();

            // Sync counters from DB
            $commentaire->setLikesCount($likeRepo->countByCommentaireAndType($commentaireId, 'like'));
            $commentaire->setDislikesCount($likeRepo->countByCommentaireAndType($commentaireId, 'dislike'));
            $em->flush();

        } catch (\Throwable $e) {
            $publicationId = $publicationId ?? null;
            $this->addFlash('error', 'Erreur vote : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_front_publication_show', [
            'id' => $publicationId ?? 0
        ]);
    }

    #[Route('/{id}/dislike', name: 'app_front_commentaire_dislike', methods: ['POST'])]
    public function dislike(
        Commentaire $commentaire,
        EntityManagerInterface $em,
        CommentaireLikeRepository $likeRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            $this->addFlash('error', 'Vous devez être connecté pour voter.');
            return $this->redirectToRoute('app_login');
        }
        try {
            $commentaireId = $commentaire->getId();
            $publicationId = $commentaire->getPublication()->getId();

            // Get current vote (safe even with legacy duplicate rows)
            $existingVote = $likeRepo->findByUserAndCommentaire($user->getId(), $commentaireId);
            $currentType = $existingVote ? $existingVote->getType() : null;

            // Delete ALL existing votes (cleans up legacy duplicates)
            $likeRepo->deleteAllForUserAndCommentaire($user->getId(), $commentaireId);
            $em->clear();

            // Re-fetch detached entities
            $commentaire = $em->find(Commentaire::class, $commentaireId);

            if ($currentType !== 'dislike') {
                $vote = new CommentaireLike();
                $vote->setCommentaire($commentaire);
                $vote->setUser($em->getReference(Utilisateur::class, $user->getId()));
                $vote->setType('dislike');
                $em->persist($vote);
            }
            $em->flush();

            // Sync counters from DB
            $commentaire->setLikesCount($likeRepo->countByCommentaireAndType($commentaireId, 'like'));
            $commentaire->setDislikesCount($likeRepo->countByCommentaireAndType($commentaireId, 'dislike'));
            $em->flush();

        } catch (\Throwable $e) {
            $publicationId = $publicationId ?? null;
            $this->addFlash('error', 'Erreur vote : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_front_publication_show', [
            'id' => $publicationId ?? 0
        ]);
    }

    #[Route('/{id}/delete', name: 'app_front_commentaire_delete', methods: ['POST'])]
    public function delete(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException("Vous devez être connecté.");
        }

        $isCommentAuthor = ($commentaire->getUser() === $user);
        $isPublicationAuthor = ($commentaire->getPublication()->getUser() === $user);

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
        $user = $this->getUser();
        if (!$user instanceof Utilisateur || $commentaire->getUser() !== $user) {
            throw $this->createAccessDeniedException("Seul l'auteur peut modifier son commentaire !");
        }

        $contenu = $request->request->get('contenu');
        $gifUrl = $request->request->get('gif_url');

        if ($contenu !== null || $gifUrl !== null) {
            $commentaire->setContenu($contenu ?? '');
            $commentaire->setGifUrl($gifUrl);
            $commentaire->setDateCommentaire(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Commentaire mis à jour !');

            return $this->redirectToRoute('app_front_publication_show', [
                'id' => $commentaire->getPublication()->getId()
            ]);
        }

        return $this->render('front_commentaire/edit.html.twig', [
            'commentaire' => $commentaire
        ]);
    }
}
