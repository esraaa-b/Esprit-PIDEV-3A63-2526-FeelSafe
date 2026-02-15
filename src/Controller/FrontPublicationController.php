<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use App\Repository\CommentaireLikeRepository;
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
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
                ->orderBy('p.pinnedAt', 'DESC')
                ->addOrderBy('p.datePublication', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            // Toutes les publications non supprimées, triées par épinglage puis date
            $publications = $publicationRepository->findBy(
                ['isDeleted' => false],
                ['pinnedAt' => 'DESC', 'datePublication' => 'DESC']
            );
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
    public function show(
        Publication $publication,
        CommentaireRepository $commentaireRepo,
        CommentaireLikeRepository $likeRepo,
        EntityManagerInterface $em
    ): Response {
        $rootComments = $commentaireRepo->findRootByPublication($publication);

        // Build user votes map: { commentId => 'like'|'dislike' }
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);
        $userVotes = $fakeUser
            ? $likeRepo->findUserVotesForPublication($fakeUser->getId(), $publication->getId())
            : [];

        return $this->render('front_publication/show.html.twig', [
            'publication' => $publication,
            'rootComments' => $rootComments,
            'userVotes' => $userVotes,
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

            // Gestion de l'épinglage
            if ($form->get('isPinned')->getData()) {
                $publication->setPinnedAt(new \DateTime());
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

        // Gestion de l'épinglage
        if ($form->get('isPinned')->getData()) {
            if ($publication->getPinnedAt() === null) {
                $publication->setPinnedAt(new \DateTime());
            }
        } else {
            $publication->setPinnedAt(null);
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

    #[Route('/front/publication/{id}/pin', name: 'app_front_publication_pin', methods: ['POST'])]
    public function pin(Publication $publication, EntityManagerInterface $em): Response
    {
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);
        if ($publication->getUser() !== $fakeUser) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_front_publication');
        }

        $publication->setPinnedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Publication épinglée !');
        return $this->redirectToRoute('app_front_publication');
    }

    #[Route('/front/publication/{id}/unpin', name: 'app_front_publication_unpin', methods: ['POST'])]
    public function unpin(Publication $publication, EntityManagerInterface $em): Response
    {
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);
        if ($publication->getUser() !== $fakeUser) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_front_publication');
        }

        $publication->setPinnedAt(null);
        $em->flush();

        $this->addFlash('success', 'Publication désépinglée.');
        return $this->redirectToRoute('app_front_publication');
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

    #[Route('/front/publication/{id}/summarize', name: 'app_front_publication_summarize', methods: ['POST'])]
    public function summarize(Publication $publication, HttpClientInterface $client): Response
    {
        $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? '';

        if (!$apiKey) {
            return $this->json(['error' => 'API Key not configured'], 500);
        }

        try {
            $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://localhost:8000', // Requis par OpenRouter
                    'X-Title' => 'FeelSafe Forum',
                ],
                'json' => [
                    'model' => 'google/gemini-2.0-flash-001',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant qui résume des publications de forum de santé mentale. Fais un résumé très court (2 phrases max), bienveillant et structuré en français.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Titre: " . $publication->getTitre() . "\n\nContenu: " . $publication->getContenu(),
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray();
            $summary = $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu générer de résumé.';

            return $this->json(['summary' => $summary]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur IA: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/front/publication/ai-action', name: 'app_front_publication_ai', methods: ['POST'])]
    public function aiAction(Request $request, HttpClientInterface $client): Response
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';
        $text = $data['text'] ?? '';

        if (empty($text)) {
            return $this->json(['error' => 'Le texte est vide.'], 400);
        }

        $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? '';
        if (!$apiKey) {
            return $this->json(['error' => 'Clé API non configurée.'], 500);
        }

        $prompt = "";
        if ($action === 'reformulate') {
            $prompt = "Réécris le texte suivant de manière plus fluide, élégante et percutante pour un forum de santé mentale. Garde un ton bienveillant et professionnel. DONNE UNIQUEMENT LE TEXTE REFORMULÉ, SANS RIEN AJOUTER AVANT OU APRÈS (pas de 'Voici la reformulation', pas de feedback). Voici le texte : \n\n" . $text;
        } elseif ($action === 'correct') {
            $prompt = "Corrige uniquement les fautes d'orthographe et de grammaire du texte suivant. Ne reformule pas le style, garde le sens original intact. DONNE UNIQUEMENT LE TEXTE CORRIGÉ, SANS RIEN AJOUTER AVANT OU APRÈS (pas de 'Voici le texte corrigé'). Voici le texte : \n\n" . $text;
        } else {
            return $this->json(['error' => 'Action invalide.'], 400);
        }

        try {
            $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://localhost:8000',
                    'X-Title' => 'FeelSafe Forum',
                ],
                'json' => [
                    'model' => 'google/gemini-2.0-flash-001',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ],
            ]);

            $result = $response->toArray();
            $generatedText = $result['choices'][0]['message']['content'] ?? null;

            if (!$generatedText) {
                return $this->json(['error' => 'L\'IA n\'a pas pu générer de réponse.'], 500);
            }

            return $this->json(['text' => trim($generatedText)]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur IA: ' . $e->getMessage()], 500);
        }
    }
}
