<?php

namespace App\Controller\Admin;

use App\Entity\JournalEmotionnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\JournalEmotionnelType;
use App\Enum\EmotionEnum;

#[Route('/admin/journals')]
class JournalEmotionnelController extends AbstractController
{
#[Route('/', name: 'admin_journal_index')]
public function index(Request $request, EntityManagerInterface $em): Response
{
    $email   = $request->query->get('email');
    $emotion = $request->query->get('emotion');

    $qb = $em->getRepository(JournalEmotionnel::class)
        ->createQueryBuilder('j')
        ->leftJoin('j.utilisateur', 'u')
        ->addSelect('u')
        ->orderBy('j.dateCreation', 'DESC');

    if ($email) {
        $qb->andWhere('u.email LIKE :email')
           ->setParameter('email', '%' . $email . '%');
    }

    if ($emotion) {
        $qb->andWhere('j.emotion = :emotion')
           ->setParameter('emotion', $emotion);
    }

    $journals = $qb->getQuery()->getResult();

    return $this->render('admin/journal/index.html.twig', [
        'journals' => $journals,
        'emotions' => EmotionEnum::cases(), // ✅ send enum to Twig
        'filters' => [
            'email' => $email,
            'emotion' => $emotion,
        ]
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

#[Route('/{id}/edit', name: 'admin_journal_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, JournalEmotionnel $journal, EntityManagerInterface $em): Response
{
    $form = $this->createForm(JournalEmotionnelType::class, $journal, [
        'journal' => $journal,
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        // ✅ GESTION MANUELLE DE L'ÉMOTION
        $emotionValue = $request->request->get('emotion'); // Récupère la valeur du radio
        if ($emotionValue) {
            $emotion = \App\Enum\EmotionEnum::tryFrom($emotionValue);
            if ($emotion) {
                $journal->setEmotion($emotion);
            }
        }
        
        // IMAGE
        if ($form->has('removeImage') && $form->get('removeImage')->getData()) {
            if ($journal->getImage()) {
                @unlink($this->getParameter('journal_images_dir') . '/' . $journal->getImage());
                $journal->setImage(null);
            }
        }

        if ($imageFile = $form->get('imageFile')->getData()) {
            $imageName = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('journal_images_dir'),
                $imageName
            );
            $journal->setImage($imageName);
        }

        // AUDIO
        if ($form->has('removeAudio') && $form->get('removeAudio')->getData()) {
            if ($journal->getAudio()) {
                @unlink($this->getParameter('journal_audio_dir') . '/' . $journal->getAudio());
                $journal->setAudio(null);
            }
        }

        if ($audioFile = $form->get('audioFile')->getData()) {
            $audioName = uniqid() . '.' . $audioFile->guessExtension();
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

    return $this->render('admin/journal/_edit_modal.html.twig', [
        'form' => $form->createView(),
        'journal' => $journal,
        'emotions' => \App\Enum\EmotionEnum::cases(),
    ]);
}


}
