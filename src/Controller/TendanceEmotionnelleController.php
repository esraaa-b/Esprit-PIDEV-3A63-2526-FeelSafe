<?php

namespace App\Controller;

use App\Entity\TendanceEmotionnelle;
use App\Repository\TendanceEmotionnelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    $generator->generateForMonth($user, $month, $year);

    $this->addFlash('success', 'Tendances générées avec succès.');

    return $this->redirectToRoute('app_tendance_emotionnelle_index');
}

    #[Route('/{id}', name: 'app_tendance_emotionnelle_delete', methods: ['POST'])]
    public function delete(Request $request, TendanceEmotionnelle $tendanceEmotionnelle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tendanceEmotionnelle->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tendanceEmotionnelle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tendance_emotionnelle_index', [], Response::HTTP_SEE_OTHER);
    }
}
