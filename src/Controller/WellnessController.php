<?php

namespace App\Controller;

use App\Entity\ActiviteBienEtre;
use App\Entity\SessionActivite;
use App\Repository\ActiviteBienEtreRepository;
use App\Repository\SessionActiviteRepository;
use App\Service\WeatherService;
use App\Service\ActivityRecommendationService;
use App\Service\AIWellnessQuizService;
use App\Service\WellnessInsightsService;  // ← AJOUTE CETTE LIGNE
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/wellness')]
class WellnessController extends AbstractController
{
    #[Route('', name: 'app_wellness')]
    public function index(
        ActiviteBienEtreRepository $activiteRepo,
        SessionActiviteRepository $sessionRepo,
        WeatherService $weatherService,
        ActivityRecommendationService $recommendationService,
        WellnessInsightsService $insightsService  // NOUVEAU
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        // Récupérer les sessions de l'utilisateur (7 derniers jours)
        $dateDebut = new \DateTime('-7 days');
        $sessions = $sessionRepo->createQueryBuilder('s')
            ->where('s.utilisateur = :user')
            ->andWhere('s.dateDebut >= :dateDebut')
            ->setParameter('user', $user)
            ->setParameter('dateDebut', $dateDebut)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculer les stats de la semaine
        $weeklyProgress = [];
        $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $insights = $insightsService->getInsights($user);
        
        for ($i = 0; $i < 7; $i++) {
            $date = new \DateTime("-$i days");
            $dayName = $daysOfWeek[(int)$date->format('N') - 1];
            
            $dayMinutes = 0;
            $dayCompleted = false;
            
            foreach ($sessions as $session) {
                if ($session->getDateDebut()->format('Y-m-d') === $date->format('Y-m-d')) {
                    if ($session->getDureeReelle()) {
                        $dayMinutes += $session->getDureeReelle();
                    }
                    if ($session->getStatutSession()->value === 'COMPLETEE') {
                        $dayCompleted = true;
                    }
                }
            }
            
            $weeklyProgress[] = [
                'day' => $dayName,
                'completed' => $dayCompleted,
                'minutes' => $dayMinutes
            ];
        }
        
        $weeklyProgress = array_reverse($weeklyProgress);
        
        $totalMinutes = array_sum(array_column($weeklyProgress, 'minutes'));
        $completedDays = count(array_filter($weeklyProgress, fn($day) => $day['completed']));

        // Activités recommandées (actives uniquement)
        $recommendedActivities = $activiteRepo->createQueryBuilder('a')
            ->where('a.estActive = :active')
            ->setParameter('active', true)
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        // Dernières sessions
        $recentSessions = $sessionRepo->createQueryBuilder('s')
            ->where('s.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('s.dateDebut', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        // NOUVEAU : Météo actuelle
        $weather = $weatherService->getCurrentWeather();
        $weatherEmoji = $weatherService->getWeatherEmoji();
        $weatherRecommendation = $weatherService->getWeatherRecommendation();
        $isGoodForOutdoor = $weatherService->isGoodForOutdoorActivity();

        // NOUVEAU : Recommandations intelligentes
        $smartRecommendations = $recommendationService->getSmartRecommendations($user, 4);
        $activityOfTheMoment = $recommendationService->getActivityOfTheMoment($user);

        return $this->render('dashboard/wellness/index.html.twig', [
            'weeklyProgress' => $weeklyProgress,
            'totalMinutes' => $totalMinutes,
            'completedDays' => $completedDays,
            'recommendedActivities' => $recommendedActivities,
            'recentSessions' => $recentSessions,
            
            // Nouvelles variables météo
            'weather' => $weather,
            'weatherEmoji' => $weatherEmoji,
            'weatherRecommendation' => $weatherRecommendation,
            'isGoodForOutdoor' => $isGoodForOutdoor,
            
            // Nouvelles variables recommandations IA
            'smartRecommendations' => $smartRecommendations,
            'activityOfTheMoment' => $activityOfTheMoment,

            'insights' => $insights,
        ]);
    }

    #[Route('/activites', name: 'app_wellness_activites')]
    public function activites(
        Request $request,
        ActiviteBienEtreRepository $activiteRepo
    ): Response {
        // Récupérer les filtres
        $type = $request->query->get('type');
        $niveau = $request->query->get('niveau');
        $categorie = $request->query->get('categorie');

        $qb = $activiteRepo->createQueryBuilder('a')
            ->where('a.estActive = :active')
            ->setParameter('active', true);

        if ($type) {
            $qb->andWhere('a.typeActivite = :type')
               ->setParameter('type', $type);
        }

        if ($niveau) {
            $qb->andWhere('a.niveauDifficulte = :niveau')
               ->setParameter('niveau', $niveau);
        }

        if ($categorie) {
            $qb->andWhere('a.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }

        $activites = $qb->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        // Récupérer toutes les valeurs uniques pour les filtres
        $types = $activiteRepo->createQueryBuilder('a')
            ->select('DISTINCT a.typeActivite')
            ->where('a.estActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $categories = $activiteRepo->createQueryBuilder('a')
            ->select('DISTINCT a.categorie')
            ->where('a.estActive = :active')
            ->andWhere('a.categorie IS NOT NULL')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/wellness/activites/index.html.twig', [
            'activites' => $activites,
            'types' => array_column($types, 'typeActivite'),
            'categories' => array_column($categories, 'categorie'),
            'currentType' => $type,
            'currentNiveau' => $niveau,
            'currentCategorie' => $categorie,
        ]);
    }

    #[Route('/activites/{id}', name: 'app_wellness_activite_show')]
    public function show(
        ActiviteBienEtre $activite,
        SessionActiviteRepository $sessionRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        // Statistiques de l'activité pour cet utilisateur
        $userSessions = $sessionRepo->createQueryBuilder('s')
            ->where('s.utilisateur = :user')
            ->andWhere('s.activite = :activite')
            ->setParameter('user', $user)
            ->setParameter('activite', $activite)
            ->getQuery()
            ->getResult();

        $totalSessions = count($userSessions);
        $completedSessions = count(array_filter($userSessions, fn($s) => $s->getStatutSession()->value === 'COMPLETEE'));
        
        $totalMinutes = 0;
        foreach ($userSessions as $session) {
            if ($session->getDureeReelle()) {
                $totalMinutes += $session->getDureeReelle();
            }
        }

        return $this->render('dashboard/wellness/activites/show.html.twig', [
            'activite' => $activite,
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'totalMinutes' => $totalMinutes,
        ]);
    }

    #[Route('/activites/{id}/start', name: 'app_wellness_session_start', methods: ['POST'])]
    public function startSession(
        ActiviteBienEtre $activite,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        // Créer une nouvelle session
        $session = new SessionActivite();
        $session->setUtilisateur($user);
        $session->setActivite($activite);
        $session->setDateDebut(new \DateTimeImmutable());
        $session->setStatutSession(\App\Enum\StatutSession::EN_COURS);

        $em->persist($session);
        $em->flush();

        return $this->redirectToRoute('app_wellness_session_active', ['id' => $session->getId()]);
    }

    #[Route('/sessions/{id}/active', name: 'app_wellness_session_active')]
    public function activeSession(SessionActivite $session): Response
    {
        // Vérifier que la session appartient à l'utilisateur
        if ($session->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('dashboard/wellness/sessions/active.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/sessions/{id}/complete', name: 'app_wellness_session_complete', methods: ['GET', 'POST'])]
    public function completeSession(
        Request $request,
        SessionActivite $session,
        EntityManagerInterface $em
    ): Response {
        // Vérifier que la session appartient à l'utilisateur
        if ($session->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $session->setDateFin(new \DateTimeImmutable());
            $session->setStatutSession(\App\Enum\StatutSession::COMPLETEE);
            
            // Durée réelle (en minutes)
            $debut = $session->getDateDebut();
            $fin = $session->getDateFin();
            $duree = ($fin->getTimestamp() - $debut->getTimestamp()) / 60;
            $session->setDureeReelle((int)$duree);

            // Humeur après
            if ($request->request->get('humeur_apres')) {
    $session->setHumeurApres(\App\Enum\HumeurEnum::from(
        strtoupper($request->request->get('humeur_apres'))
    ));
}
            if ($request->request->get('score_humeur_apres')) {
                $session->setScoreHumeurApres((int)$request->request->get('score_humeur_apres'));
            }
            if ($request->request->get('emotion_apres')) {
                $session->setEmotionApres($request->request->get('emotion_apres'));
            }

            // Évaluation
            if ($request->request->get('note_satisfaction')) {
                $session->setNoteSatisfaction((int)$request->request->get('note_satisfaction'));
            }
 if ($request->request->get('impact_percu')) {
    $session->setImpactPercu(\App\Enum\ImpactPercu::from(
        strtoupper($request->request->get('impact_percu'))
    ));
}
            if ($request->request->get('commentaire')) {
                $session->setCommentaire($request->request->get('commentaire'));
            }
            
            $session->setEstObjectifAtteint($request->request->get('est_objectif_atteint') === '1');

            $em->flush();

            $this->addFlash('success', 'Session terminée avec succès ! 🎉');
            return $this->redirectToRoute('app_wellness');
        }

        return $this->render('dashboard/wellness/sessions/complete.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/sessions/history', name: 'app_wellness_sessions_history')]
    public function history(SessionActiviteRepository $sessionRepo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        $sessions = $sessionRepo->createQueryBuilder('s')
            ->where('s.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('s.dateDebut', 'DESC')
            ->setMaxResults(500)
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/wellness/sessions/history.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/stats', name: 'app_wellness_stats')]
    public function stats(SessionActiviteRepository $sessionRepo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        // Toutes les sessions de l'utilisateur
        $sessions = $sessionRepo->createQueryBuilder('s')
            ->where('s.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('s.dateDebut', 'ASC')
            ->setMaxResults(500)
            ->getQuery()
            ->getResult();

        // Calculer les statistiques
        $totalSessions = count($sessions);
        $completedSessions = count(array_filter($sessions, fn($s) => $s->getStatutSession()->value === 'COMPLETEE'));
        
        $totalMinutes = 0;
        $moodEvolution = [];
        
        foreach ($sessions as $session) {
            if ($session->getDureeReelle()) {
                $totalMinutes += $session->getDureeReelle();
            }
            
            // Évolution humeur
            if ($session->getScoreHumeurAvant() && $session->getScoreHumeurApres()) {
                $moodEvolution[] = [
                    'date' => $session->getDateDebut()->format('Y-m-d'),
                    'avant' => $session->getScoreHumeurAvant(),
                    'apres' => $session->getScoreHumeurApres(),
                    'evolution' => $session->getScoreHumeurApres() - $session->getScoreHumeurAvant(),
                ];
            }
        }

        // Streak (jours consécutifs)
        $streak = $this->calculateStreak($sessions);

        return $this->render('dashboard/wellness/stats.html.twig', [
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'totalMinutes' => $totalMinutes,
            'moodEvolution' => $moodEvolution,
            'streak' => $streak,
        ]);
    }

    /**
     * NOUVELLE ROUTE : Page de recommandations intelligentes
     */
    #[Route('/recommendations', name: 'app_wellness_recommendations')]
    public function recommendations(
        ActivityRecommendationService $recommendationService,
        WeatherService $weatherService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        
        $smartRecommendations = $recommendationService->getSmartRecommendations($user, 12);
        $activityOfTheMoment = $recommendationService->getActivityOfTheMoment($user);
        $weather = $weatherService->getCurrentWeather();
        $weatherCategory = $weatherService->getWeatherCategory();

        return $this->render('dashboard/wellness/recommendations.html.twig', [
            'smartRecommendations' => $smartRecommendations,
            'activityOfTheMoment' => $activityOfTheMoment,
            'weather' => $weather,
            'weatherCategory' => $weatherCategory,
        ]);
    }

    /**
 * NOUVELLE ROUTE : Page de démarrage du quiz
 */
#[Route('/quiz', name: 'app_wellness_quiz_start')]
public function quizStart(): Response
{
    return $this->render('dashboard/wellness/quiz/start.html.twig');
}

/**
 * NOUVELLE ROUTE : Questions du quiz
 */
#[Route('/quiz/questions', name: 'app_wellness_quiz_questions')]
public function quizQuestions(AIWellnessQuizService $quizService): Response
{
    $questions = $quizService->getQuizQuestions();
    
    return $this->render('dashboard/wellness/quiz/questions.html.twig', [
        'questions' => $questions,
    ]);
}

/**
 * NOUVELLE ROUTE : Analyse des réponses et résultats
 */
#[Route('/quiz/results', name: 'app_wellness_quiz_results', methods: ['POST'])]
public function quizResults(
    Request $request,
    AIWellnessQuizService $quizService
): Response {
    $answers = $request->request->all();
    
    // Analyser avec l'IA
    $results = $quizService->analyzeAndRecommend($answers);
    
    return $this->render('dashboard/wellness/quiz/results.html.twig', [
        'results' => $results,
        'answers' => $answers,
    ]);
}

    /**
     * NOUVELLE ROUTE : Actualiser la météo (AJAX)
     */
    #[Route('/weather/refresh', name: 'app_wellness_weather_refresh', methods: ['GET'])]
    public function refreshWeather(
        WeatherService $weatherService
    ): Response {
        $weather = $weatherService->getCurrentWeather();
        
        return $this->json([
            'success' => true,
            'weather' => $weather,
            'emoji' => $weatherService->getWeatherEmoji(),
            'recommendation' => $weatherService->getWeatherRecommendation(),
        ]);
    }

    private function calculateStreak(array $sessions): int
    {
        if (empty($sessions)) {
            return 0;
        }

        $streak = 0;
        $currentDate = new \DateTime('today');
        
        // Grouper les sessions par date
        $sessionsByDate = [];
        foreach ($sessions as $session) {
            $date = $session->getDateDebut()->format('Y-m-d');
            if (!isset($sessionsByDate[$date])) {
                $sessionsByDate[$date] = [];
            }
            $sessionsByDate[$date][] = $session;
        }

        // Calculer le streak
        while (true) {
            $dateKey = $currentDate->format('Y-m-d');
            
            if (!isset($sessionsByDate[$dateKey])) {
                break;
            }
            
            // Vérifier s'il y a au moins une session complétée ce jour
            $hasCompleted = false;
            foreach ($sessionsByDate[$dateKey] as $session) {
                if ($session->getStatutSession()->value === 'COMPLETEE') {
                    $hasCompleted = true;
                    break;
                }
            }
            
            if (!$hasCompleted) {
                break;
            }
            
            $streak++;
            $currentDate->modify('-1 day');
        }

        return $streak;
    }
}