<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForumController extends AbstractController
{
    #[Route('/dashboard/forum', name: 'app_forum')]
    public function index(): Response
    {
        // Categories data
        $categories = [
            ['id' => 'all', 'name' => 'Tous', 'count' => 156],
            ['id' => 'anxiety', 'name' => 'Anxiété', 'count' => 42],
            ['id' => 'depression', 'name' => 'Dépression', 'count' => 38],
            ['id' => 'stress', 'name' => 'Stress', 'count' => 35],
            ['id' => 'relationships', 'name' => 'Relations', 'count' => 28],
            ['id' => 'self-care', 'name' => 'Auto-soins', 'count' => 13],
        ];

        // Topics data
        $topics = [
            [
                'id' => 1,
                'title' => 'Comment gérer l\'anxiété sociale au quotidien ?',
                'author' => 'Marie L.',
                'avatar' => null,
                'category' => 'Anxiété',
                'replies' => 24,
                'likes' => 56,
                'views' => 342,
                'lastReply' => 'Il y a 2h',
                'pinned' => true,
                'excerpt' => 'Je cherche des conseils pour mieux gérer mon anxiété sociale, surtout au travail...',
            ],
            [
                'id' => 2,
                'title' => 'Techniques de respiration qui fonctionnent vraiment',
                'author' => 'Thomas R.',
                'avatar' => null,
                'category' => 'Stress',
                'replies' => 18,
                'likes' => 89,
                'views' => 567,
                'lastReply' => 'Il y a 5h',
                'pinned' => false,
                'excerpt' => 'J\'ai testé plusieurs techniques et je voulais partager celles qui m\'ont le plus aidé...',
            ],
            [
                'id' => 3,
                'title' => 'Partage d\'expérience : 1 an de thérapie',
                'author' => 'Sophie M.',
                'avatar' => null,
                'category' => 'Dépression',
                'replies' => 45,
                'likes' => 123,
                'views' => 892,
                'lastReply' => 'Il y a 1j',
                'pinned' => false,
                'excerpt' => 'Après un an de suivi, je souhaitais partager mon parcours et ce qui a changé pour moi...',
            ],
            [
                'id' => 4,
                'title' => 'Comment parler de santé mentale à ses proches ?',
                'author' => 'Lucas D.',
                'avatar' => null,
                'category' => 'Relations',
                'replies' => 31,
                'likes' => 78,
                'views' => 456,
                'lastReply' => 'Il y a 2j',
                'pinned' => false,
                'excerpt' => 'C\'est parfois difficile d\'aborder le sujet avec sa famille. Quels sont vos conseils ?',
            ],
            [
                'id' => 5,
                'title' => 'Routine matinale pour bien commencer la journée',
                'author' => 'Emma B.',
                'avatar' => null,
                'category' => 'Auto-soins',
                'replies' => 27,
                'likes' => 95,
                'views' => 678,
                'lastReply' => 'Il y a 3j',
                'pinned' => false,
                'excerpt' => 'Je partage ma routine matinale qui m\'aide à avoir une meilleure énergie...',
            ],
        ];

        return $this->render('dashboard/forum/index.html.twig', [
            'categories' => $categories,
            'topics' => $topics,
        ]);
    }
}