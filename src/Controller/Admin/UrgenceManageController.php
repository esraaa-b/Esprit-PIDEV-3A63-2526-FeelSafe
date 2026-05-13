<?php

namespace App\Controller\Admin;

use App\Entity\Urgence;
use App\Repository\UrgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emergency/urgence')]
class UrgenceManageController extends AbstractController
{
    #[Route('/list', name: 'admin_urgence_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('admin/emergency/urgence_list.html.twig');
    }

    #[Route('/data', name: 'admin_urgence_data', methods: ['GET'])]
    public function getData(UrgenceRepository $repository): JsonResponse
    {
        $urgences = $repository->findAll();

        $data = [];
        foreach ($urgences as $urgence) {
            $data[] = [
                'id' => $urgence->getId(),
                'type' => $urgence->getTypeUrgence(),
                'description' => $urgence->getDescription(),
                'gravity' => $urgence->getNiveauGravite(),
                'status' => $urgence->getStatut(),
                'date' => $urgence->getDateHeure() ? $urgence->getDateHeure()->format('Y-m-d H:i:s') : null,
                'userId' => $urgence->getIdUtilisateur(),
            ];
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/create', name: 'admin_urgence_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation matching Java
            $type = trim($data['type'] ?? '');
            if (empty($type)) {
                return $this->json(['success' => false, 'error' => 'Le type d\'urgence est requis'], Response::HTTP_BAD_REQUEST);
            }
            if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/', $type)) {
                return $this->json(['success' => false, 'error' => 'Le type ne doit contenir que des lettres, espaces et tirets'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($type) < 3) {
                return $this->json(['success' => false, 'error' => 'Le type doit contenir au moins 3 caractères'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($type) > 50) {
                return $this->json(['success' => false, 'error' => 'Le type ne peut pas dépasser 50 caractères'], Response::HTTP_BAD_REQUEST);
            }

            $description = trim($data['description'] ?? '');
            if (strlen($description) < 5) {
                return $this->json(['success' => false, 'error' => 'La description doit contenir au moins 5 caractères'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($description) > 500) {
                return $this->json(['success' => false, 'error' => 'La description ne peut pas dépasser 500 caractères'], Response::HTTP_BAD_REQUEST);
            }

            $gravity = (int) ($data['gravity'] ?? 0);
            if ($gravity < 1 || $gravity > 5) {
                return $this->json(['success' => false, 'error' => 'La gravité doit être entre 1 et 5'], Response::HTTP_BAD_REQUEST);
            }

            $status = $data['status'] ?? Urgence::STATUT_EN_ATTENTE;
            $allowedStatuses = [Urgence::STATUT_EN_ATTENTE, Urgence::STATUT_PRISE_EN_CHARGE, Urgence::STATUT_RESOLUE];
            if (!in_array($status, $allowedStatuses)) {
                return $this->json(['success' => false, 'error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
            }

            $userId = (int) ($data['userId'] ?? 0);
            if ($userId <= 0) {
                return $this->json(['success' => false, 'error' => 'ID utilisateur invalide'], Response::HTTP_BAD_REQUEST);
            }

            $urgence = new Urgence();
            $urgence->setTypeUrgence($type);
            $urgence->setDescription($description);
            $urgence->setNiveauGravite($gravity);
            $urgence->setStatut($status);
            $urgence->setDateHeure(new \DateTime());
            $urgence->setIdUtilisateur($userId);

            $entityManager->persist($urgence);
            $entityManager->flush();

            return $this->json(['success' => true, 'id' => $urgence->getId()]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/update/{id}', name: 'admin_urgence_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, UrgenceRepository $repository): JsonResponse
    {
        try {
            $urgence = $repository->find($id);
            if (!$urgence) {
                return $this->json(['success' => false, 'error' => 'Urgence non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            // Same validation as create
            $type = trim($data['type'] ?? '');
            if (empty($type) || strlen($type) < 3 || strlen($type) > 50 || !preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/', $type)) {
                return $this->json(['success' => false, 'error' => 'Type invalide'], Response::HTTP_BAD_REQUEST);
            }

            $description = trim($data['description'] ?? '');
            if (strlen($description) < 5 || strlen($description) > 500) {
                return $this->json(['success' => false, 'error' => 'Description invalide (5-500 caractères)'], Response::HTTP_BAD_REQUEST);
            }

            $gravity = (int) ($data['gravity'] ?? 0);
            if ($gravity < 1 || $gravity > 5) {
                return $this->json(['success' => false, 'error' => 'Gravité invalide'], Response::HTTP_BAD_REQUEST);
            }

            $status = $data['status'] ?? null;
            $allowedStatuses = [Urgence::STATUT_EN_ATTENTE, Urgence::STATUT_PRISE_EN_CHARGE, Urgence::STATUT_RESOLUE];
            if (!in_array($status, $allowedStatuses)) {
                return $this->json(['success' => false, 'error' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
            }

            $userId = (int) ($data['userId'] ?? 0);
            if ($userId <= 0) {
                return $this->json(['success' => false, 'error' => 'ID utilisateur invalide'], Response::HTTP_BAD_REQUEST);
            }

            $urgence->setTypeUrgence($type);
            $urgence->setDescription($description);
            $urgence->setNiveauGravite($gravity);
            $urgence->setStatut($status);
            $urgence->setIdUtilisateur($userId);

            $entityManager->flush();

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', name: 'admin_urgence_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager, UrgenceRepository $repository): JsonResponse
    {
        try {
            $urgence = $repository->find($id);
            if (!$urgence) {
                return $this->json(['success' => false, 'error' => 'Urgence non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $entityManager->remove($urgence);
            $entityManager->flush();

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/filter', name: 'admin_urgence_filter', methods: ['GET'])]
    public function filter(Request $request, UrgenceRepository $repository): JsonResponse
    {
        $gravity = $request->query->get('gravity');

        $qb = $repository->createQueryBuilder('u');

        if ($gravity && is_numeric($gravity)) {
            $qb->andWhere('u.niveauGravite = :gravity')
                ->setParameter('gravity', (int) $gravity);
        }

        $urgences = $qb->getQuery()->getResult();

        $data = [];
        foreach ($urgences as $urgence) {
            $data[] = [
                'id' => $urgence->getId(),
                'type' => $urgence->getTypeUrgence(),
                'description' => $urgence->getDescription(),
                'gravity' => $urgence->getNiveauGravite(),
                'status' => $urgence->getStatut(),
                'date' => $urgence->getDateHeure() ? $urgence->getDateHeure()->format('Y-m-d H:i:s') : null,
                'userId' => $urgence->getIdUtilisateur(),
            ];
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/{id}', name: 'admin_urgence_get', methods: ['GET'])]
    public function getOne(int $id, UrgenceRepository $repository): JsonResponse
    {
        $urgence = $repository->find($id);
        if (!$urgence) {
            return $this->json(['success' => false, 'error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $urgence->getId(),
                'type' => $urgence->getTypeUrgence(),
                'description' => $urgence->getDescription(),
                'gravity' => $urgence->getNiveauGravite(),
                'status' => $urgence->getStatut(),
                'userId' => $urgence->getIdUtilisateur(),
            ]
        ]);
    }
}