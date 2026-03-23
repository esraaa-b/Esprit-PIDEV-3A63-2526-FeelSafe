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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Form\PublicationType;
use App\Repository\TranslationCacheRepository;
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
            $publications = $publicationRepository->findByTopic($tag);
        } elseif ($search) {
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
        int $id,
        CommentaireRepository $commentaireRepo,
        CommentaireLikeRepository $likeRepo,
        EntityManagerInterface $em,
        PublicationRepository $publicationRepo
    ): Response {
        $publication = $publicationRepo->find($id);
        if (!$publication) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_front_publication');
        }
        $rootComments = $commentaireRepo->findRootByPublication($publication);
        $fakeUser = $em->getRepository(Utilisateur::class)->find(1);
        $userVotes = [];
        if ($fakeUser) {
            try {
                $userVotes = $likeRepo->findUserVotesForPublication($fakeUser->getId(), $publication->getId());
            } catch (\Throwable $e) {
                $userVotes = [];
            }
        }

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

        $user = $entityManager->getRepository(Utilisateur::class)->find(1);
        $publication->setUser($user);

        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contenu = $publication->getContenu();
            $hasText = $contenu !== null && strlen(trim($contenu)) >= 10;
            if (!$hasText) {
                $form->get('contenu')->addError(new \Symfony\Component\Form\FormError('Le contenu doit contenir au moins 10 caractères.'));
                return $this->render('front_publication/new.html.twig', ['form' => $form->createView()]);
            }

            $publication->setDatePublication(new \DateTimeImmutable());

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

            if ($form->get('isPinned')->getData()) {
                $publication->setPinnedAt(new \DateTimeImmutable());
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
            $contenu = $publication->getContenu();
            $hasText = $contenu !== null && strlen(trim($contenu)) >= 10;
            if (!$hasText) {
                $this->addFlash('error', 'Le contenu doit contenir au moins 10 caractères.');
                return $this->render('front_publication/edit.html.twig', ['publication' => $publication, 'form' => $form->createView()]);
            }

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

            if ($form->get('isPinned')->getData()) {
                if ($publication->getPinnedAt() === null) {
                    $publication->setPinnedAt(new \DateTimeImmutable());
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
        $publication->setLikesCount($publication->getLikesCount() + 1);

        $liker = $em->getRepository(Utilisateur::class)->find(2);
        if (!$liker) $liker = $em->getRepository(Utilisateur::class)->find(1);

        $publication->setNotificationMessage("Votre publication a reçu un nouveau Like !");
        $publication->setNotificationRead(false);
        $publication->setNotificationDate(new \DateTimeImmutable());

        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/front/publication/{id}/dislike', name: 'app_front_publication_dislike', methods: ['POST'])]
    public function dislike(Publication $publication, Request $request, EntityManagerInterface $em): Response
    {
        $publication->setDislikesCount($publication->getDislikesCount() + 1);

        $disliker = $em->getRepository(Utilisateur::class)->find(2);
        if (!$disliker) $disliker = $em->getRepository(Utilisateur::class)->find(1);

        $publication->setNotificationMessage("Votre publication a reçu un nouveau Dislike.");
        $publication->setNotificationRead(false);
        $publication->setNotificationDate(new \DateTimeImmutable());

        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    public function badge(PublicationRepository $repository): Response
    {
        try {
            $unread = $repository->findUnreadNotifications(1);
            $count = count($unread);
        } catch (\Throwable $e) {
            $unread = [];
            $count = 0;
        }

        return $this->render('components/_notification_badge.html.twig', [
            'count' => $count,
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

        $publication->setPinnedAt(new \DateTimeImmutable());
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
        $apiKey = $_ENV['OPENROUTER_API_KEY_OVERRIDE'] ?? $_SERVER['OPENROUTER_API_KEY_OVERRIDE'] ?? ($_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? '');

        if (!$apiKey) {
            return $this->json(['error' => 'API Key not configured'], 500);
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

        $apiKey = $_ENV['OPENROUTER_API_KEY_OVERRIDE'] ?? $_SERVER['OPENROUTER_API_KEY_OVERRIDE'] ?? ($_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? '');
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

    #[Route('/front/publication/analyze-image', name: 'app_front_publication_analyze_image', methods: ['POST'])]
    public function analyzeImage(Request $request, HttpClientInterface $client): Response
    {
        $imageFile = $request->files->get('image');
        if (!$imageFile instanceof UploadedFile) {
            return $this->json(['error' => 'Aucune image fournie. Sélectionnez une image puis cliquez sur le bouton.'], 400);
        }

        $apiKey = $_ENV['OPENROUTER_API_KEY_OVERRIDE'] ?? $_SERVER['OPENROUTER_API_KEY_OVERRIDE'] ?? ($_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? '');
        if (!$apiKey) {
            return $this->json(['error' => 'Clé API non configurée. Ajoutez OPENROUTER_API_KEY dans .env'], 500);
        }

        try {
            $content = base64_encode(file_get_contents($imageFile->getPathname()));
            $mime = $imageFile->getMimeType() ?: 'image/jpeg';
            $url = 'data:' . $mime . ';base64,' . $content;

            $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => $request->getSchemeAndHttpHost(),
                    'X-Title' => 'FeelSafe Forum',
                ],
                'json' => [
                    'model' => 'google/gemini-2.0-flash-001',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => "Décris cette image en une phrase simple : ce qu'on voit (objets, scène, paysage) et l'émotion ou l'ambiance qu'elle dégage. Réponds UNIQUEMENT avec cette phrase en français, sans préambule.",
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $url],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray();
            $description = $data['choices'][0]['message']['content'] ?? null;
            if ($description === null || trim($description) === '') {
                return $this->json(['error' => 'Impossible d\'analyser l\'image.'], 500);
            }
            return $this->json(['description' => trim(strip_tags($description))]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur analyse: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/front/publication/translate', name: 'app_front_publication_translate', methods: ['POST'])]
    public function translate(Request $request, HttpClientInterface $client, TranslationCacheRepository $cacheRepo, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $text = trim((string) ($data['text'] ?? ''));
        $targetLang = trim((string) ($data['target_lang'] ?? 'fr'));

        if ($text === '') {
            return $this->json(['error' => 'Texte vide.'], 400);
        }

        $sourceHash = hash('sha256', $text);
        try {
            $cached = $cacheRepo->findCached($sourceHash, $targetLang);
            if ($cached) {
                return $this->json(['translated' => $cached->getTranslatedText()]);
            }
        } catch (\Throwable $e) {
            // Table translation_cache peut ne pas exister
        }

        $apiKey = $_ENV['OPENROUTER_API_KEY_OVERRIDE'] ?? $_SERVER['OPENROUTER_API_KEY_OVERRIDE'] ?? ($_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? '');
        if (!$apiKey) {
            return $this->json(['error' => 'Clé API non configurée. Ajoutez OPENROUTER_API_KEY dans .env'], 500);
        }

        $langNames = ['fr' => 'français', 'en' => 'anglais', 'es' => 'espagnol', 'de' => 'allemand', 'ar' => 'arabe'];
        $targetName = $langNames[$targetLang] ?? $targetLang;

        try {
            $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => $request->getSchemeAndHttpHost(),
                    'X-Title' => 'FeelSafe Forum',
                ],
                'json' => [
                    'model' => 'google/gemini-2.0-flash-001',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "Traduis le texte suivant en " . $targetName . ". Réponds UNIQUEMENT avec la traduction, sans guillemets ni commentaire.\n\n" . $text,
                        ],
                    ],
                ],
            ]);

            $result = $response->toArray();
            $content = $result['choices'][0]['message']['content'] ?? null;
            if ($content === null || $content === '') {
                return $this->json(['error' => 'Traduction impossible.'], 500);
            }
            $translated = trim(strip_tags(preg_replace('/^["\']|["\']$/u', '', $content)));

            try {
                $cache = new \App\Entity\TranslationCache();
                $cache->setSourceTextHash($sourceHash);
                $cache->setTargetLang($targetLang);
                $cache->setTranslatedText($translated);
                $em->persist($cache);
                $em->flush();
            } catch (\Throwable $e) {
                // Ignorer si table absente
            }

            return $this->json(['translated' => $translated]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur traduction: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/front/report', name: 'app_front_report', methods: ['POST'])]
    public function report(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true) ?: [];
        if (!$this->isCsrfTokenValid('report', $data['_token'] ?? '')) {
            return $this->json(['error' => 'Token invalide.'], 403);
        }
        $type = $data['type'] ?? '';
        $id = (int) ($data['id'] ?? 0);
        $reason = $data['reason'] ?? '';
        $description = isset($data['description']) ? trim((string) $data['description']) : null;

        $validReasons = ['spam', 'contenu_offensant', 'harcelement', 'autre'];
        if (!in_array($reason, $validReasons, true)) {
            return $this->json(['error' => 'Raison invalide.'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté pour signaler.'], 403);
        }

        if ($type === 'publication') {
            $publication = $em->getRepository(Publication::class)->find($id);
            if (!$publication) {
                return $this->json(['error' => 'Publication introuvable.'], 404);
            }
            $publication->setIsReported(true);
            $publication->setReportReason($reason);
            $publication->setReportDescription($description);
            $publication->setReportedAt(new \DateTimeImmutable());
        } elseif ($type === 'comment') {
            $commentaire = $em->getRepository(Commentaire::class)->find($id);
            if (!$commentaire) {
                return $this->json(['error' => 'Commentaire introuvable.'], 404);
            }
            $commentaire->setIsReported(true);
            $commentaire->setReportReason($reason);
            $commentaire->setReportDescription($description);
            $commentaire->setReportedAt(new \DateTimeImmutable());
        } else {
            return $this->json(['error' => 'Type invalide (publication ou comment).'], 400);
        }

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Signalement enregistré.']);
    }
}
