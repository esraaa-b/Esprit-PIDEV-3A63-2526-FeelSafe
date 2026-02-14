<?php

namespace App\Controller\Admin;

use App\Entity\Urgence;
use App\Entity\Intervention;
use App\Form\EmergencyInterventionType;
use App\Repository\UrgenceRepository;
use App\Repository\InterventionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emergency')]
class EmergencyController extends AbstractController
{
    #[Route('', name: 'admin_emergency', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UrgenceRepository $urgenceRepository,
        InterventionRepository $interventionRepository
    ): Response {
        // FOR EMERGENCY FORM
        $urgence = null;
        $editEmergencyId = $request->query->get('edit_emergency_id');

        if ($editEmergencyId) {
            $urgence = $urgenceRepository->find($editEmergencyId);
        }

        $emergencyForm = $this->createForm(EmergencyInterventionType::class, null, [
            'edit_urgence' => $urgence,
            'edit_intervention' => null, // Null for emergency form
            'form_type' => 'emergency', // Add this to distinguish forms
        ]);

        $emergencyForm->handleRequest($request);

        // FOR INTERVENTION FORM
        $intervention = null;
        $editInterventionId = $request->query->get('edit_intervention_id');

        if ($editInterventionId) {
            $intervention = $interventionRepository->find($editInterventionId);
        }

        $interventionForm = $this->createForm(EmergencyInterventionType::class, null, [
            'edit_urgence' => null, // Null for intervention form
            'edit_intervention' => $intervention,
            'form_type' => 'intervention', // Add this to distinguish forms
        ]);

        $interventionForm->handleRequest($request);

        // Handle Emergency Form Submission
        if ($emergencyForm->isSubmitted() && $emergencyForm->isValid()) {
            try {
                $formData = $emergencyForm->getData();

                if ($urgence) {
                    // EDIT Emergency
                    $urgence->setTypeUrgence($emergencyForm->get('emergencyType')->getData());
                    $urgence->setDescription($emergencyForm->get('emergencyDescription')->getData());
                    $urgence->setSeverityLevel($emergencyForm->get('severityLevel')->getData());
                    $urgence->setStatus($emergencyForm->get('emergencyStatus')->getData());
                    $urgence->setLocation($emergencyForm->get('location')->getData());

                    $entityManager->flush();
                    $this->addFlash('success', 'Emergency updated successfully!');
                } else {
                    // CREATE Emergency
                    $newUrgence = new Urgence();
                    $newUrgence->setTypeUrgence($emergencyForm->get('emergencyType')->getData());
                    $newUrgence->setDescription($emergencyForm->get('emergencyDescription')->getData());
                    $newUrgence->setSeverityLevel($emergencyForm->get('severityLevel')->getData());
                    $newUrgence->setStatus($emergencyForm->get('emergencyStatus')->getData());
                    $newUrgence->setLocation($emergencyForm->get('location')->getData());
                    $newUrgence->setCreatedAt(new \DateTime());

                    $entityManager->persist($newUrgence);
                    $entityManager->flush();
                    $this->addFlash('success', 'Emergency created successfully!');
                }

                return $this->redirectToRoute('admin_emergency');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        // Handle Intervention Form Submission
        if ($interventionForm->isSubmitted() && $interventionForm->isValid()) {
            try {
                if ($intervention) {
                    // EDIT Intervention
                    $intervention->setInterventionType($interventionForm->get('interventionType')->getData());
                    $intervention->setNotes($interventionForm->get('interventionNotes')->getData());
                    $intervention->setResult($interventionForm->get('result')->getData());
                    $intervention->setInterventionDate($interventionForm->get('interventionDate')->getData());

                    $entityManager->flush();
                    $this->addFlash('success', 'Intervention updated successfully!');
                } else {
                    // CREATE Intervention
                    $newIntervention = new Intervention();
                    $newIntervention->setInterventionType($interventionForm->get('interventionType')->getData());
                    $newIntervention->setNotes($interventionForm->get('interventionNotes')->getData());
                    $newIntervention->setResult($interventionForm->get('result')->getData());
                    $newIntervention->setInterventionDate($interventionForm->get('interventionDate')->getData());

                    // IMPORTANT: Add emergency selection field to form
                    $urgenceForIntervention = $interventionForm->get('urgence')->getData();
                    if ($urgenceForIntervention) {
                        $newIntervention->setUrgence($urgenceForIntervention);
                    }

                    $entityManager->persist($newIntervention);
                    $entityManager->flush();
                    $this->addFlash('success', 'Intervention created successfully!');
                }

                return $this->redirectToRoute('admin_emergency');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        $emergencies = $urgenceRepository->findAll();
        $interventions = $interventionRepository->findAll();

        return $this->render('admin/emergency/index.html.twig', [
            'emergencies' => $emergencies,
            'interventions' => $interventions,
            'emergencyForm' => $emergencyForm->createView(),
            'interventionForm' => $interventionForm->createView(),
            'edit_emergency_id' => $editEmergencyId,
            'edit_intervention_id' => $editInterventionId,
        ]);
    }

    // Keep the delete methods the same...
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

    #[Route('/intervention/delete/{id}', name: 'admin_intervention_delete', methods: ['POST'])]
    public function deleteIntervention(Request $request, Intervention $intervention, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $intervention->getId(), $request->request->get('_token'))) {
            $entityManager->remove($intervention);
            $entityManager->flush();
            $this->addFlash('success', 'Intervention deleted successfully!');
        }

        return $this->redirectToRoute('admin_emergency');
    }
}