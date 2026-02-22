<?php

namespace App\Controller;

use App\Entity\Urgence;
use App\Repository\UrgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Psr\Log\LoggerInterface;

class EmergencyController extends AbstractController
{
    #[Route('/dashboard/emergency', name: 'app_emergency', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UrgenceRepository $urgenceRepository,
        LoggerInterface $logger
    ): Response {

        $user = $this->getCurrentUser($logger);

        $logger->info('User accessed emergency page', [
            'identifier' => $user->getUserIdentifier()
        ]);

        /* ===================== HANDLE FORM ===================== */

        if ($request->isMethod('POST') && $request->request->has('submit_emergency')) {

            try {
                $urgence = new Urgence();

                $description = $request->request->get('description');
                $urgencyLevel = $request->request->get('urgency_level');
                $location = $request->request->get('location');

                $urgence->setTypeUrgence('User Report');
                $urgence->setDescription($description);
                $urgence->setLocation($location ?: 'Non spécifié');

                $severityMap = [
                    'high' => 5,
                    'medium' => 3,
                    'low' => 1
                ];

                $urgence->setSeverityLevel($severityMap[$urgencyLevel] ?? 3);
                $urgence->setStatus('Pending');

                // IMPORTANT: compatible with Doctrine
                $urgence->setCreatedAt(new \DateTime());

                // this will work if Urgence::setUser() expects your real entity
                $urgence->setUser($user);

                $entityManager->persist($urgence);
                $entityManager->flush();

                $this->addFlash('success', 'Votre demande d\'urgence a été envoyée avec succès!');

            } catch (\Throwable $e) {

                $logger->error('Emergency save failed', [
                    'error' => $e->getMessage()
                ]);

                $this->addFlash('error', 'Une erreur est survenue.');
            }

            return $this->redirectToRoute('app_emergency');
        }

        /* ===================== LIST USER EMERGENCIES ===================== */

        $userEmergencies = $urgenceRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('dashboard/emergency/index.html.twig', [
            'emergencies' => $userEmergencies,
        ]);
    }

    /**
     * Always returns authenticated user
     */
    private function getCurrentUser(LoggerInterface $logger): UserInterface
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            $logger->warning('Anonymous access blocked');
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
