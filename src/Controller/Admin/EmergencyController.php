<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Urgence;
use App\Entity\Utilisateur;
use App\Repository\UrgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UtilisateurRepository;

#[Route('/admin/emergency')]
class EmergencyController extends AbstractController
{
    #[Route('', name: 'admin_emergency', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
    EntityManagerInterface $entityManager,
    UrgenceRepository $urgenceRepository,
    UtilisateurRepository $utilisateurRepository  // inject directly
): Response {

        // Handle Admin-Created Emergency Form Submission
        if ($request->isMethod('POST') && $request->request->has('admin_create_emergency')) {

            try {
                $urgence = new Urgence();

                // Get form data
                $userId = $request->request->get('user_id');
                $urgencyLevel = $request->request->get('urgency_level');
                $location = $request->request->get('location');
                $description = $request->request->get('description');

                // Find the selected user
                $user = $entityManager->getRepository(Utilisateur::class)->find($userId);

                if (!$user) {
                    throw new \Exception('User not found');
                }

                // Map urgency level to severity level (like in user's form)
                $severityMap = [
                    'high' => 5,
                    'medium' => 3,
                    'low' => 1
                ];

                // Set emergency data
                $urgence->setTypeUrgence('Admin Created');
                $urgence->setDescription($description);
                $urgence->setLocation($location ?: 'Non spécifié');
                $urgence->setSeverityLevel($severityMap[$urgencyLevel] ?? 3);
                $urgence->setStatus('Pending');
                $urgence->setUser($user);

                $entityManager->persist($urgence);
                $entityManager->flush();

                $this->addFlash('success', 'Emergency created successfully for ' . $user->getPrenom() . ' ' . $user->getNom());

            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating emergency: ' . $e->getMessage());
            }

            return $this->redirectToRoute('admin_emergency');
        }

            $emergencies = $urgenceRepository->findAllOrderedByDate();
            $users = $utilisateurRepository->findAllForDropdown();


        return $this->render('admin/emergency/index.html.twig', [
            'emergencies' => $emergencies,
            'users' => $users,
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_emergency_delete', methods: ['POST'])]
    public function deleteUrgence(Request $request, Urgence $urgence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $urgence->getId(), $request->request->get('_token'))) {
            $entityManager->remove($urgence);
            $entityManager->flush();
            $this->addFlash('success', 'Emergency deleted successfully!');
        }

        return $this->redirectToRoute('admin_emergency');
    }

    #[Route('/{id}/status/{status}', name: 'admin_emergency_status', methods: ['POST'])]
    public function updateStatus(int $id, string $status, Request $request, EntityManagerInterface $entityManager, UrgenceRepository $urgenceRepository): JsonResponse
    {
        try {
            if (!$request->headers->get('X-Requested-With') == 'XMLHttpRequest') {
                return $this->json(['success' => false, 'error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
            }

            $emergency = $urgenceRepository->find($id);

            if (!$emergency) {
                return $this->json(['success' => false, 'error' => 'Emergency not found'], Response::HTTP_NOT_FOUND);
            }

            $allowedStatuses = ['Pending', 'In Progress', 'Resolved', 'Closed'];
            if (!in_array($status, $allowedStatuses)) {
                return $this->json(['success' => false, 'error' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
            }

            $emergency->setStatus($status);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => "Emergency #{$id} marked as {$status}",
                'new_status' => $status
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error updating status: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/emergency/{id}/details', name: 'admin_emergency_details', methods: ['GET'])]
    public function getEmergencyDetails(int $id, UrgenceRepository $urgenceRepository): JsonResponse
    {
        try {
            $emergency = $urgenceRepository->find($id);

            if (!$emergency) {
                return $this->json(['success' => false, 'error' => 'Emergency not found'], Response::HTTP_NOT_FOUND);
            }

            $user = $emergency->getUser();

            return $this->json([
                'success' => true,
                'emergency' => [
                    'id' => $emergency->getId(),
                    'type' => $emergency->getTypeUrgence(),
                    'description' => $emergency->getDescription(),
                    'severity' => $emergency->getSeverityLevel(),
                    'status' => $emergency->getStatus(),
                    'location' => $emergency->getLocation(),
                    'created_at' => $emergency->getCreatedAt() ? $emergency->getCreatedAt()->format('Y-m-d H:i:s') : null,
                    'user' => $user ? [
                        'name' => $user->getPrenom() . ' ' . $user->getNom(),
                        'email' => $user->getEmail(),
                        'phone' => $user->getTelephone()
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error fetching details: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}