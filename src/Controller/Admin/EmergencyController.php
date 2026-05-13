<?php

namespace App\Controller\Admin;

use App\Entity\Urgence;
use App\Entity\Intervention;
use App\Repository\UrgenceRepository;
use App\Repository\InterventionRepository;
use App\Service\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/emergency')]
class EmergencyController extends AbstractController
{
    #[Route('', name: 'admin_emergency', methods: ['GET'])]
    public function index(
        UrgenceRepository $urgenceRepository,
        InterventionRepository $interventionRepository
    ): Response {
        // Get all urgences
        $urgences = $urgenceRepository->findAll();

        // Get interventions en cours
        $interventionsEnCours = $interventionRepository->findBy(['statut' => Intervention::STATUT_EN_COURS]);

        // Count stats
        $urgencesCritiques = $urgenceRepository->countCritiques();
        $urgencesNonTraitees = $urgenceRepository->countNonTraitees();
        $interventionsEnCoursCount = count($interventionsEnCours);

        return $this->render('admin/emergency/index.html.twig', [
            'urgences' => $urgences,
            'interventionsEnCours' => $interventionsEnCours,
            'urgencesCritiques' => $urgencesCritiques,
            'urgencesNonTraitees' => $urgencesNonTraitees,
            'interventionsEnCoursCount' => $interventionsEnCoursCount,
        ]);
    }

    #[Route('/data', name: 'admin_emergency_data', methods: ['GET'])]
    public function getData(
        UrgenceRepository $urgenceRepository,
        InterventionRepository $interventionRepository
    ): JsonResponse {
        $urgences = $urgenceRepository->findAll();
        $interventionsEnCours = $interventionRepository->findBy(['statut' => Intervention::STATUT_EN_COURS]);

        $urgencesData = [];
        foreach ($urgences as $urgence) {
            $urgencesData[] = [
                'id' => $urgence->getId(),
                'type' => $urgence->getTypeUrgence(),
                'description' => $urgence->getDescription(),
                'gravity' => $urgence->getNiveauGravite(),
                'status' => $urgence->getStatut(),
                'date' => $urgence->getDateHeure() ? $urgence->getDateHeure()->format('Y-m-d H:i:s') : null,
                'userId' => $urgence->getIdUtilisateur(),
            ];
        }

        $interventionsData = [];
        foreach ($interventionsEnCours as $intervention) {
            $interventionsData[] = [
                'id' => $intervention->getId(),
                'idUrgence' => $intervention->getIdUrgence(),
                'type' => $intervention->getTypeIntervention(),
                'status' => $intervention->getStatut(),
                'date' => $intervention->getDateHeure() ? $intervention->getDateHeure()->format('Y-m-d H:i:s') : null,
                'notes' => $intervention->getNotes(),
                'adminId' => $intervention->getIdAdmin(),
            ];
        }

        return $this->json([
            'success' => true,
            'urgencesCritiques' => $urgenceRepository->countCritiques(),
            'urgencesNonTraitees' => $urgenceRepository->countNonTraitees(),
            'interventionsEnCoursCount' => count($interventionsEnCours),
            'urgences' => $urgencesData,
            'interventions' => $interventionsData,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'admin_emergency_status', methods: ['POST'])]
    public function updateStatus(int $id, string $status, Request $request, EntityManagerInterface $entityManager, UrgenceRepository $urgenceRepository): JsonResponse
    {
        try {
            $emergency = $urgenceRepository->find($id);

            if (!$emergency) {
                return $this->json(['success' => false, 'error' => 'Emergency not found'], Response::HTTP_NOT_FOUND);
            }

            $allowedStatuses = [Urgence::STATUT_EN_ATTENTE, Urgence::STATUT_PRISE_EN_CHARGE, Urgence::STATUT_RESOLUE];
            if (!in_array($status, $allowedStatuses)) {
                return $this->json(['success' => false, 'error' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
            }

            $emergency->setStatut($status);
            $entityManager->flush();

            return $this->json(['success' => true, 'new_status' => $status]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/intervention/create', name: 'admin_intervention_create', methods: ['POST'])]
    public function createIntervention(Request $request, EntityManagerInterface $entityManager, UrgenceRepository $urgenceRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $urgence = $urgenceRepository->find($data['idUrgence']);
            if (!$urgence) {
                return $this->json(['success' => false, 'error' => 'Urgence not found'], Response::HTTP_NOT_FOUND);
            }

            // Update urgency status to "prise en charge"
            $urgence->setStatut(Urgence::STATUT_PRISE_EN_CHARGE);

            // Create intervention
            $intervention = new Intervention();
            $intervention->setIdUrgence($data['idUrgence']);
            $intervention->setTypeIntervention($data['type']);
            $intervention->setStatut(Intervention::STATUT_EN_COURS);
            $intervention->setDateHeure(new \DateTime());
            $intervention->setNotes($data['notes'] ?? '');
            $intervention->setIdAdmin($this->getUser()->getId());

            // Optionally increase gravity
            if (isset($data['increaseGravity']) && $data['increaseGravity'] === true) {
                $newGravity = min($urgence->getNiveauGravite() + 1, 5);
                $urgence->setNiveauGravite($newGravity);
                $intervention->setNotes(($intervention->getNotes() ?: '') . " [Gravité augmentée à $newGravity]");
            }

            $entityManager->persist($intervention);
            $entityManager->flush();

            return $this->json(['success' => true, 'intervention_id' => $intervention->getId()]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/intervention/{id}', name: 'admin_intervention_delete', methods: ['DELETE'])]
    public function deleteIntervention(int $id, EntityManagerInterface $entityManager, InterventionRepository $interventionRepository): JsonResponse
    {
        try {
            $intervention = $interventionRepository->find($id);
            if (!$intervention) {
                return $this->json(['success' => false, 'error' => 'Intervention not found'], Response::HTTP_NOT_FOUND);
            }

            $entityManager->remove($intervention);
            $entityManager->flush();

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/urgence/delete/{id}', name: 'admin_urgence_delete', methods: ['DELETE'])]
    public function deleteUrgence(int $id, EntityManagerInterface $entityManager, UrgenceRepository $urgenceRepository): JsonResponse
    {
        try {
            $urgence = $urgenceRepository->find($id);
            if (!$urgence) {
                return $this->json(['success' => false, 'error' => 'Urgence not found'], Response::HTTP_NOT_FOUND);
            }

            $entityManager->remove($urgence);
            $entityManager->flush();

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/export/urgences', name: 'admin_export_urgences', methods: ['GET'])]
    public function exportUrgences(UrgenceRepository $urgenceRepository, PdfExportService $pdfExportService): Response
    {
        $urgences = $urgenceRepository->findAll();
        return $pdfExportService->exportUrgencesToPDF($urgences, 'Liste des Urgences - FeelSafe');
    }

    #[Route('/export/interventions', name: 'admin_export_interventions', methods: ['GET'])]
    public function exportInterventions(InterventionRepository $interventionRepository, PdfExportService $pdfExportService): Response
    {
        $interventions = $interventionRepository->findAll();
        return $pdfExportService->exportInterventionsToPDF($interventions, 'Liste des Interventions - FeelSafe');
    }
}