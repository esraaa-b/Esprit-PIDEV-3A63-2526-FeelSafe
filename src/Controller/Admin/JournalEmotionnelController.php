<?php

namespace App\Controller\Admin;

use App\Entity\JournalEmotionnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\JournalEmotionnelType;

#[Route('/admin/journals')]
class JournalEmotionnelController extends AbstractController
{
    #[Route('/', name: 'admin_journal_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $journals = $em->getRepository(JournalEmotionnel::class)
            ->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('admin/journal/index.html.twig', [
            'journals' => $journals,
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_journal_delete', methods: ['POST'])]
    public function delete(
        JournalEmotionnel $journal,
        EntityManagerInterface $em
    ): Response {
        $em->remove($journal);
        $em->flush();

        $this->addFlash('success', 'Journal supprimé avec succès.');

        return $this->redirectToRoute('admin_journal_index');
    }
  #[Route('/admin/journal/{id}/edit', name: 'admin_journal_edit')]
public function edit(
    Request $request,
    JournalEmotionnel $journal,
    EntityManagerInterface $em
): Response {
    $form = $this->createForm(JournalEmotionnelType::class, $journal);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        /* ================= IMAGE ================= */

        // Remove image
        if ($form->has('removeImage') && $form->get('removeImage')->getData()) {
            if ($journal->getImage()) {
                @unlink($this->getParameter('journal_images_dir') . '/' . $journal->getImage());
                $journal->setImage(null);
            }
        }

        // Upload new image
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile) {
            $imageName = uniqid().'.'.$imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('journal_images_dir'),
                $imageName
            );

            $journal->setImage($imageName);
        }

        /* ================= AUDIO ================= */

        // Remove audio
        if ($form->has('removeAudio') && $form->get('removeAudio')->getData()) {
            if ($journal->getAudio()) {
                @unlink($this->getParameter('journal_audio_dir') . '/' . $journal->getAudio());
                $journal->setAudio(null);
            }
        }

        // Upload new audio
        $audioFile = $form->get('audioFile')->getData();
        if ($audioFile) {
            $audioName = uniqid().'.'.$audioFile->guessExtension();
            $audioFile->move(
                $this->getParameter('journal_audio_dir'),
                $audioName
            );

            $journal->setAudio($audioName);
        }

        $em->flush();

        $this->addFlash('success', 'Journal modifié avec succès.');
        return $this->redirectToRoute('admin_journal_index');
    }

    return $this->render('admin/journal/edit.html.twig', [
        'form' => $form,
        'journal' => $journal,
    ]);
}


}
