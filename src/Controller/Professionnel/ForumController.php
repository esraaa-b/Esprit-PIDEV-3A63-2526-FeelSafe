<?php

namespace App\Controller\Professionnel;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;

class ForumController extends BaseDashboardController
{
    #[Route('/dashboard/forum', name: 'app_forum')]
    public function index(): Response
    {
        $categories = [
            ['id' => 'all', 'name' => 'Tous', 'count' => 156],
            ['id' => 'anxiety', 'name' => 'Anxiété', 'count' => 42],
            ['id' => 'depression', 'name' => 'Dépression', 'count' => 38],
            ['id' => 'stress', 'name' => 'Stress', 'count' => 35],
        ];

        $topics = [
            [
                'title' => 'Comment gérer l\'anxiété sociale ?',
                'author' => 'Marie L.',
                'category' => 'Anxiété',
                'replies' => 24,
                'likes' => 56,
                'views' => 342,
                'lastReply' => 'Il y a 2h',
                'pinned' => true,
                'excerpt' => 'Je cherche des conseils...',
            ],
        ];

        return $this->render('professionnel/forum/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'categories' => $categories,
                'topics' => $topics,
            ]
        ));
    }
}