<?php

namespace App\Controller\Professionnel;

use App\Entity\JournalEmotionnel;
use App\Enum\EmotionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\Client\BaseDashboardController;

#[Route('/dashboard/journal')]
class JournalController extends BaseDashboardController
{
    #[Route('', name: 'app_journal')]
    public function index(EntityManagerInterface $em): Response
    {
        $journals = $em->getRepository(JournalEmotionnel::class)
            ->findBy(
                ['utilisateur' => $this->getUser()],
                ['dateCreation' => 'DESC']
            );

        return $this->render('professionnel/journal/index.html.twig', array_merge(
            $this->getUserData(),
            [
                'journals' => $journals,
                'moodOptions' => EmotionEnum::cases(),
            ]
        ));
    }

    #[Route('/new', name: 'app_journal_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::from($request->request->get('emotion')));
        $journal->setContenu($request->request->get('contenu'));
        $journal->setDateCreation(new \DateTime());
        $journal->setUtilisateur($this->getUser());

        // Gestion de l'image
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('journals_images_directory'),
                $newFilename
            );
            $journal->setImage($newFilename);
        }

        // Gestion de l'audio
        $audioFile = $request->files->get('audio');
        if ($audioFile) {
            $newFilename = uniqid() . '.' . $audioFile->guessExtension();
            $audioFile->move(
                $this->getParameter('journals_audio_directory'),
                $newFilename
            );
            $journal->setAudio($newFilename);
        }

        $em->persist($journal);
        $em->flush();

        $this->addFlash('success', 'Journal créé avec succès');
        return $this->redirectToRoute('app_journal');
    }

    #[Route('/{id}/edit', name: 'app_journal_edit')]
    public function edit(
        JournalEmotionnel $journal,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('OWNER', $journal);

        if ($request->isMethod('POST')) {
            $journal->setEmotion(EmotionEnum::from($request->request->get('emotion')));
            $journal->setContenu($request->request->get('contenu'));

            // Gestion de l'image
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                // Supprimer l'ancienne image
                if ($journal->getImage()) {
                    $oldPath = $this->getParameter('journals_images_directory') . '/' . $journal->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('journals_images_directory'),
                    $newFilename
                );
                $journal->setImage($newFilename);
            }

            // Gestion de l'audio
            $audioFile = $request->files->get('audio');
            if ($audioFile) {
                // Supprimer l'ancien audio
                if ($journal->getAudio()) {
                    $oldPath = $this->getParameter('journals_audio_directory') . '/' . $journal->getAudio();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                $newFilename = uniqid() . '.' . $audioFile->guessExtension();
                $audioFile->move(
                    $this->getParameter('journals_audio_directory'),
                    $newFilename
                );
                $journal->setAudio($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Journal modifié avec succès');
            return $this->redirectToRoute('app_journal');
        }

        return $this->render('dashboard/journal/edit.html.twig', array_merge(
            $this->getUserData(),
            [
                'journal' => $journal,
                'moodOptions' => EmotionEnum::cases(),
            ]
        ));
    }

    #[Route('/{id}/delete', name: 'app_journal_delete', methods: ['POST'])]
    public function delete(JournalEmotionnel $journal, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('OWNER', $journal);

        // Supprimer les fichiers
        if ($journal->getImage()) {
            $imagePath = $this->getParameter('journals_images_directory') . '/' . $journal->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        if ($journal->getAudio()) {
            $audioPath = $this->getParameter('journals_audio_directory') . '/' . $journal->getAudio();
            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
        }

        $em->remove($journal);
        $em->flush();

        $this->addFlash('success', 'Journal supprimé avec succès');
        return $this->redirectToRoute('app_journal');
    }
}