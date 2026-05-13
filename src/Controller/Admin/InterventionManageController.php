<?php

namespace App\Controller\Admin;

use App\Entity\Intervention;
use App\Entity\Urgence;
use App\Repository\InterventionRepository;
use App\Repository\UrgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emergency/intervention')]
class InterventionManageController extends AbstractController
{
    #[Route('/list', name: 'admin_intervention_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('admin/emergency/intervention_list.html.twig');
    }

    #[Route('/data', name: 'admin_intervention_data', methods: ['GET'])]
    public function getData(InterventionRepository $repository): JsonResponse
    {
        $interventions = $repository->findAll();

        $data = [];
        foreach ($interventions as $intervention) {
            $data[] = [
                'id' => $intervention->getId(),
                'idUrgence' => $intervention->getIdUrgence(),
                'type' => $intervention->getTypeIntervention(),
                'status' => $intervention->getStatut(),
                'date' => $intervention->getDateHeure() ? $intervention->getDateHeure()->format('Y-m-d H:i:s') : null,
                'notes' => $intervention->getNotes(),
                'adminId' => $intervention->getIdAdmin(),
            ];
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/create', name: 'admin_intervention_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UrgenceRepository $urgenceRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate ID Urgence
            $idUrgence = (int) ($data['idUrgence'] ?? 0);
            if ($idUrgence <= 0) {
                return $this->json(['success' => false, 'error' => 'ID Urgence invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Check if urgency exists (matching Java)
            $urgence = $urgenceRepository->find($idUrgence);
            if (!$urgence) {
                return $this->json(['success' => false, 'error' => "L'urgence avec ID {$idUrgence} n'existe pas"], Response::HTTP_BAD_REQUEST);
            }

            // Check if urgency is already taken care of (matching Java)
            if (
                $urgence->getStatut() === Urgence::STATUT_PRISE_EN_CHARGE ||
                $urgence->getStatut() === Urgence::STATUT_RESOLUE
            ) {
                return $this->json(['success' => false, 'error' => "Cette urgence est déjà {$urgence->getStatut()}"], Response::HTTP_BAD_REQUEST);
            }

            // Validate Type
            $type = $data['type'] ?? '';
            $allowedTypes = [Intervention::TYPE_APPEL, Intervention::TYPE_EQUIPE, Intervention::TYPE_ESCALADE];
            if (!in_array($type, $allowedTypes)) {
                return $this->json(['success' => false, 'error' => 'Type d\'intervention invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Validate Status
            $status = $data['status'] ?? Intervention::STATUT_EN_COURS;
            $allowedStatuses = [Intervention::STATUT_EN_COURS, Intervention::STATUT_TERMINEE, Intervention::STATUT_ANNULEE];
            if (!in_array($status, $allowedStatuses)) {
                return $this->json(['success' => false, 'error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Validate ID Admin
            $idAdmin = (int) ($data['adminId'] ?? 0);
            if ($idAdmin <= 0) {
                return $this->json(['success' => false, 'error' => 'ID Admin invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Validate Notes (max 500 characters)
            $notes = $data['notes'] ?? '';
            if (strlen($notes) > 500) {
                return $this->json(['success' => false, 'error' => 'Les notes ne peuvent pas dépasser 500 caractères'], Response::HTTP_BAD_REQUEST);
            }

            // Check if we should increase gravity (from InterventionForm)
            $increaseGravity = $data['increaseGravity'] ?? false;
            if ($increaseGravity) {
                $newGravity = min($urgence->getNiveauGravite() + 1, 5);
                $urgence->setNiveauGravite($newGravity);
                $notes .= " [Gravité augmentée à {$newGravity}]";
            }

            // Update urgency status to "prise en charge" (matching Java)
            $urgence->setStatut(Urgence::STATUT_PRISE_EN_CHARGE);

            // Create intervention
            $intervention = new Intervention();
            $intervention->setIdUrgence($idUrgence);
            $intervention->setTypeIntervention($type);
            $intervention->setStatut($status);
            $intervention->setDateHeure(new \DateTime());
            $intervention->setNotes($notes ?: 'Aucune note fournie');
            $intervention->setIdAdmin($idAdmin);

            $entityManager->persist($intervention);
            $entityManager->flush();

            return $this->json(['success' => true, 'id' => $intervention->getId()]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/update/{id}', name: 'admin_intervention_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, InterventionRepository $repository, UrgenceRepository $urgenceRepository): JsonResponse
    {
        try {
            $intervention = $repository->find($id);
            if (!$intervention) {
                return $this->json(['success' => false, 'error' => 'Intervention non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            // Validate ID Urgence (if changed)
            $newIdUrgence = (int) ($data['idUrgence'] ?? 0);
            if ($newIdUrgence <= 0) {
                return $this->json(['success' => false, 'error' => 'ID Urgence invalide'], Response::HTTP_BAD_REQUEST);
            }

            if ($newIdUrgence !== $intervention->getIdUrgence()) {
                $urgence = $urgenceRepository->find($newIdUrgence);
                if (!$urgence) {
                    return $this->json(['success' => false, 'error' => "L'urgence avec ID {$newIdUrgence} n'existe pas"], Response::HTTP_BAD_REQUEST);
                }
            }

            // Validate Type
            $type = $data['type'] ?? '';
            $allowedTypes = [Intervention::TYPE_APPEL, Intervention::TYPE_EQUIPE, Intervention::TYPE_ESCALADE];
            if (!in_array($type, $allowedTypes)) {
                return $this->json(['success' => false, 'error' => 'Type d\'intervention invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Validate Status
            $status = $data['status'] ?? '';
            $allowedStatuses = [Intervention::STATUT_EN_COURS, Intervention::STATUT_TERMINEE, Intervention::STATUT_ANNULEE];
            if (!in_array($status, $allowedStatuses)) {
                return $this->json(['success' => false, 'error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Check if intervention is already terminated/cancelled (matching Java)
            $oldStatus = $intervention->getStatut();
            if (($oldStatus === Intervention::STATUT_TERMINEE || $oldStatus === Intervention::STATUT_ANNULEE) && $oldStatus !== $status) {
                // Java shows confirmation - we'll let frontend handle, but still allow update
                // Just log or proceed
            }

            // Validate ID Admin
            $idAdmin = (int) ($data['adminId'] ?? 0);
            if ($idAdmin <= 0) {
                return $this->json(['success' => false, 'error' => 'ID Admin invalide'], Response::HTTP_BAD_REQUEST);
            }

            // Validate Notes
            $notes = $data['notes'] ?? '';
            if (strlen($notes) > 500) {
                return $this->json(['success' => false, 'error' => 'Les notes ne peuvent pas dépasser 500 caractères'], Response::HTTP_BAD_REQUEST);
            }

            $intervention->setIdUrgence($newIdUrgence);
            $intervention->setTypeIntervention($type);
            $intervention->setStatut($status);
            $intervention->setDateHeure(new \DateTime());
            $intervention->setNotes($notes ?: '');
            $intervention->setIdAdmin($idAdmin);

            $entityManager->flush();

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', name: 'admin_intervention_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager, InterventionRepository $repository): JsonResponse
    {
        try {
            $intervention = $repository->find($id);
            if (!$intervention) {
                return $this->json(['success' => false, 'error' => 'Intervention non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $entityManager->remove($intervention);
            $entityManager->flush();

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/filter', name: 'admin_intervention_filter', methods: ['GET'])]
    public function filter(Request $request, InterventionRepository $repository): JsonResponse
    {
        $idUrgence = $request->query->get('idUrgence');

        $qb = $repository->createQueryBuilder('i');

        if ($idUrgence && is_numeric($idUrgence)) {
            $qb->andWhere('i.idUrgence = :idUrgence')
                ->setParameter('idUrgence', (int) $idUrgence);
        }

        $interventions = $qb->getQuery()->getResult();

        $data = [];
        foreach ($interventions as $intervention) {
            $data[] = [
                'id' => $intervention->getId(),
                'idUrgence' => $intervention->getIdUrgence(),
                'type' => $intervention->getTypeIntervention(),
                'status' => $intervention->getStatut(),
                'date' => $intervention->getDateHeure() ? $intervention->getDateHeure()->format('Y-m-d H:i:s') : null,
                'notes' => $intervention->getNotes(),
                'adminId' => $intervention->getIdAdmin(),
            ];
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/by-urgence/{idUrgence}', name: 'admin_intervention_by_urgence', methods: ['GET'])]
    public function getByUrgence(int $idUrgence, InterventionRepository $repository): JsonResponse
    {
        $interventions = $repository->findBy(['idUrgence' => $idUrgence]);

        $data = [];
        foreach ($interventions as $intervention) {
            $data[] = [
                'id' => $intervention->getId(),
                'type' => $intervention->getTypeIntervention(),
                'status' => $intervention->getStatut(),
                'date' => $intervention->getDateHeure() ? $intervention->getDateHeure()->format('Y-m-d H:i:s') : null,
                'notes' => $intervention->getNotes(),
                'adminId' => $intervention->getIdAdmin(),
            ];
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/{id}', name: 'admin_intervention_get', methods: ['GET'])]
    public function getOne(int $id, InterventionRepository $repository): JsonResponse
    {
        $intervention = $repository->find($id);
        if (!$intervention) {
            return $this->json(['success' => false, 'error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $intervention->getId(),
                'idUrgence' => $intervention->getIdUrgence(),
                'type' => $intervention->getTypeIntervention(),
                'status' => $intervention->getStatut(),
                'notes' => $intervention->getNotes(),
                'adminId' => $intervention->getIdAdmin(),
            ]
        ]);
    }
}