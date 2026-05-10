<?php

namespace App\Controller;

use App\Entity\TendanceEmotionnelle;
use App\Repository\TendanceEmotionnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\TendanceGenerator;

#[Route('/tendance/emotionnelle')]
final class TendanceEmotionnelleController extends AbstractController
{
    #[Route(name: 'app_tendance_emotionnelle_index', methods: ['GET'])]
    public function index(TendanceEmotionnelleRepository $repo): Response
    {
        $tendances = $repo->findBy(
            ['utilisateur' => $this->getUser()],
            ['annee' => 'DESC', 'mois' => 'DESC']
        );

        return $this->render('tendance_emotionnelle/index.html.twig', [
            'tendance_emotionnelles' => $tendances,
        ]);
    }

    #[Route('/generate/{month}/{year}', name: 'app_tendance_emotionnelle_generate')]
    public function generate(
        int $month,
        int $year,
        TendanceGenerator $generator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $generator->generateForMonth($user, $month, $year);

        $this->addFlash('success', 'Tendances générées avec succès.');

        return $this->redirectToRoute('app_tendance_emotionnelle_index');
    }

    #[Route('/{id}', name: 'app_tendance_emotionnelle_delete', methods: ['POST'])]
    public function delete(Request $request, TendanceEmotionnelle $tendanceEmotionnelle, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$tendanceEmotionnelle->getId(), $token)) {
            $entityManager->remove($tendanceEmotionnelle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tendance_emotionnelle_index', [], Response::HTTP_SEE_OTHER);
    }

    // ── NEW: called by "Calculer les tendances" in the journal page ──────────
    #[Route('/calculate/json', name: 'app_tendance_calculate_json', methods: ['POST'])]
    public function calculateJson(
        Request                        $request,
        TendanceGenerator              $generator,
        TendanceEmotionnelleRepository $repo
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('calculate_tendance', $this->extractToken($request))) {
            return $this->json(['error' => 'Token invalide'], 403);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $now  = new \DateTimeImmutable();
        $prev = $now->modify('-1 month');

        $generator->generateForMonth($user, (int) $now->format('n'),  (int) $now->format('Y'));
        $generator->generateForMonth($user, (int) $prev->format('n'), (int) $prev->format('Y'));

        return $this->json($this->buildChartData($user, $repo));
    }

    // ── NEW: called by "Réinitialiser" in the journal page ───────────────────
    #[Route('/reset/json', name: 'app_tendance_reset_json', methods: ['POST'])]
    public function resetJson(
        Request                $request,
        EntityManagerInterface $em,
        TendanceEmotionnelleRepository $repo
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('reset_tendance', $this->extractToken($request))) {
            return $this->json(['error' => 'Token invalide'], 403);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $em->createQueryBuilder()
            ->delete(TendanceEmotionnelle::class, 't')
            ->where('t.utilisateur = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return $this->json([
            'success'     => true,
            'chartLabels' => [],
            'chartData'   => [],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildChartData(mixed $user, TendanceEmotionnelleRepository $repo): array
    {
        $now    = new \DateTimeImmutable();
        $labels = [];
        $data   = [
            'tres_bien' => [],
            'bien'      => [],
            'neutre'    => [],
            'pas_bien'  => [],
            'tres_mal'  => [],
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date  = $now->modify("-{$i} month");
            $month = (int) $date->format('n');
            $year  = (int) $date->format('Y');

            $labels[] = $date->format('M Y');

            $tendances = $repo->findBy([
                'utilisateur' => $user,
                'mois'        => $month,
                'annee'       => $year,
            ]);

            $byEmotion = [];
            foreach ($tendances as $t) {
                $byEmotion[$t->getEmotion()] = $t->getTotaleOccurrences();
            }

            foreach (array_keys($data) as $emotion) {
                $data[$emotion][] = $byEmotion[$emotion] ?? 0;
            }
        }

        return [
            'chartLabels' => $labels,
            'chartData'   => $data,
        ];
    }

    private function extractToken(Request $request): string
    {
        $body = json_decode($request->getContent(), true);
        return $body['_token'] ?? $request->request->get('_token', '');
    }
}