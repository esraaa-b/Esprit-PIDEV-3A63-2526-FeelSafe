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

final class FrontPublicationController extends AbstractController
{   
    #[Route('/dashboard/forum', name: 'app_dashboard_forum')] 
    #[Route('/front/publication', name: 'app_front_publication')]
    public function index(PublicationRepository $publicationRepository, Request $request): Response
    {
        $search = $request->query->get('q'); // récupérer le mot-clé de recherche

        if ($search) {
            // Rechercher les publications dont le titre contient le mot-clé
            $publications = $publicationRepository->createQueryBuilder('p')
                ->where('p.titre LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->orderBy('p.datePublication', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            // Toutes les publications
            $publications = $publicationRepository->findBy([], ['datePublication' => 'DESC']);
        }

        return $this->render('front_publication/index.html.twig', [
            'publications' => $publications,
            'search' => $search,
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
        SluggerInterface $slugger   // 🔥 AJOUT ICI
    ): Response {
        $publication = new Publication();

        if ($request->isMethod('POST')) {

            $publication->setTitre($request->request->get('titre'));
            $publication->setContenu($request->request->get('contenu'));
            $publication->setDatePublication(new \DateTime());
            $publication->setUser($entityManager->getRepository(Utilisateur::class)->find(1));


            // IMAGE
            $imageFile = $request->files->get('image');
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
                }

                $publication->setImage($newFilename);
            }

            $entityManager->persist($publication);
            $entityManager->flush();

            return $this->redirectToRoute('app_front_publication');
        }

        return $this->render('front_publication/new.html.twig');
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
    // Si l'id dans l'URL ne correspond à rien, $publication sera null
    if (!$publication) {
        $this->addFlash('error', 'Cette publication n\'existe pas.');
        return $this->redirectToRoute('app_front_publication');
    }

    $fakeUser = $em->getRepository(Utilisateur::class)->find(1);

    if ($publication->getUser() !== $fakeUser) {
        throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cette publication !");
    }

    if ($request->isMethod('POST')) {
        $publication->setTitre($request->request->get('titre'));
        $publication->setContenu($request->request->get('contenu'));

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $newFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)) 
                           . '-' . uniqid() . '.' . $imageFile->guessExtension();
            try {
                // 🔥 Vérifiez bien si c'est 'uploads' ou 'uploads_directory' dans services.yaml
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
    ]);
}
#[Route('/front/publication/{id}/like', name: 'app_front_publication_like', methods: ['POST'])]
public function like(Publication $publication, EntityManagerInterface $em): Response
{
    // Exemple simple : incrémenter le compteur
    $publication->setLikesCount($publication->getLikesCount() + 1);
    $em->flush();

    // Redirection vers la page précédente (détail ou liste)
    return $this->redirect($request->headers->get('referer'));
}

#[Route('/front/publication/{id}/dislike', name: 'app_front_publication_dislike', methods: ['POST'])]
public function dislike(Publication $publication, EntityManagerInterface $em): Response
{
    $publication->setDislikesCount($publication->getDislikesCount() + 1);
    $em->flush();

    return $this->redirect($request->headers->get('referer'));
}
}
