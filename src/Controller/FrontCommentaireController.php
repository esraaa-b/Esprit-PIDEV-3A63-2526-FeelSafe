<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/front/commentaire')]
final class FrontCommentaireController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_front_commentaire_new', methods: ['POST'])]
    public function new(Publication $publication, Request $request, EntityManagerInterface $em): Response
    {
        $contenu = $request->request->get('contenu');

        if ($contenu) {
            $commentaire = new Commentaire();
            $commentaire->setContenu($contenu);
            $commentaire->setDateCommentaire(new \DateTime());
            $commentaire->setPublication($publication);

            // Simuler utilisateur connecté (id=1)
            $user = $em->getRepository(Utilisateur::class)->find(1);
            $commentaire->setUser($user);

            $em->persist($commentaire);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté !');
        }

        return $this->redirectToRoute('app_front_publication_show', ['id' => $publication->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_front_commentaire_delete', methods: ['POST'])]
    public function delete(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        // Simuler utilisateur connecté (id=1)
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);

        if ($commentaire->getUser() !== $fakeUser) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer ce commentaire !");
        }

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé !');

        return $this->redirectToRoute('app_front_publication_show', ['id' => $commentaire->getPublication()->getId()]);
    }
    #[Route('/{id}/edit', name: 'app_front_commentaire_edit', methods: ['GET', 'POST'])]
    public function edit(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        // Simuler utilisateur connecté (id=1)
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);

        if ($commentaire->getUser() !== $fakeUser) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier ce commentaire !");
        }

        // Si le formulaire est soumis
        $contenu = $request->request->get('contenu');
        if ($contenu !== null) {
            $commentaire->setContenu($contenu);
            $commentaire->setDateCommentaire(new \DateTime()); // optionnel : mettre à jour la date
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
