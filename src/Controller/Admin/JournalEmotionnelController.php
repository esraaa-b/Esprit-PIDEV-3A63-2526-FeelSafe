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
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin/journals')]
class JournalEmotionnelController extends AbstractController
{
#[Route('/', name: 'admin_dash')] // Ou la route que vous voulez
public function index(Request $request, EntityManagerInterface $em): Response
{
    $search = $request->get('search');
    
    // Récupérer les utilisateurs
    $qb = $em->getRepository(Utilisateur::class)->createQueryBuilder('u');
    if ($search) {
        $qb->where('u.email LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }
    $users = $qb->getQuery()->getResult();
    
    // Récupérer les journaux (si besoin)
    $journals = $em->getRepository(JournalEmotionnel::class)->findAll();
    
    return $this->render('admin/journal/index.html.twig', [
        'users' => $users,
        'journals' => $journals,
        'emotions' => EmotionEnum::cases(),
        'filters' => []
    ]);
}
// Dans App\Controller\Admin\JournalEmotionnelController.php

#[Route('/admin/journal/{id}/delete', name: 'admin_journal_delete', methods: ['POST'])]
public function delete(
    JournalEmotionnel $journal,
    EntityManagerInterface $em
): Response {
    $em->remove($journal);
    $em->flush();

    $this->addFlash('success', 'Journal supprimé avec succès.');
    return $this->redirectToRoute('admin_user_journals', [
            'id' => $journal->getUtilisateur()->getId()
        ]);
    
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
        return $this->redirectToRoute('admin_user_journals', [
            'id' => $journal->getUtilisateur()->getId()
        ]);
    }

    return $this->render('admin/journal/_edit_modal.html.twig', [
        'form' => $form->createView(),
        'journal' => $journal,
        'emotions' => \App\Enum\EmotionEnum::cases(),
    ]);
}

#[Route('/admin/users', name: 'admin_users_index')]
public function usersIndex(Request $request, EntityManagerInterface $em): Response
{
    $search = $request->get('search');
    
    $qb = $em->getRepository(Utilisateur::class)->createQueryBuilder('u');
    
    // Utiliser LIKE pour chercher ROLE_CLIENT dans le JSON
    $qb->where('u.role LIKE :role')
       ->setParameter('role', '%ROLE_CLIENT%');
    
    if ($search) {
        $qb->andWhere('u.email LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }
    
    return $this->render('admin/journal/users.html.twig', [
        'users' => $qb->getQuery()->getResult()
    ]);
}

 #[Route('/admin/api/user/{id}/journals', name: 'admin_api_user_journals', methods: ['GET'])]
    public function getUserJournals(Utilisateur $user): JsonResponse
    {
        $journals = [];
        foreach ($user->getJournaux() as $journal) {
            $journals[] = [
                'id' => $journal->getId(),
                'date' => $journal->getDateCreation()->format('d/m/Y'),
                'time' => $journal->getDateCreation()->format('H:i'),
                'emotion' => $journal->getEmotion()->value,
                'emotionLabel' => $journal->getEmotion()->label(),
                'emotionIcon' => $journal->getEmotion()->icon(),
                'emotionClass' => $this->getEmotionClass($journal->getEmotion()),
                'contenu' => $journal->getContenu(),
                'image' => $journal->getImage() ? true : false,
                'imageUrl' => $journal->getImage() ? '/uploads/journals/images/' . $journal->getImage() : null,
                'audio' => $journal->getAudio() ? true : false,
                'audioUrl' => $journal->getAudio() ? '/uploads/journals/audio/' . $journal->getAudio() : null,
            ];
        }
        
        return $this->json($journals);
    }
private function getEmotionClass(EmotionEnum $emotion): string
{
    return match($emotion) {
        EmotionEnum::TRES_BIEN => 'emotion-tres-bien',
        EmotionEnum::BIEN => 'emotion-bien',
        EmotionEnum::NEUTRE => 'emotion-neutre',
        EmotionEnum::PAS_BIEN => 'emotion-pas-bien',
        EmotionEnum::TRES_MAL => 'emotion-tres-mal',
    };
}
   #[Route('/admin/user/{id}/journals', name: 'admin_user_journals')]  // Pour journal/index.html.twig
public function userJournals(Utilisateur $user): Response
{
    return $this->render('admin/journal/index.html.twig', [  // ✅ Utilise journal/index.html.twig
        'user' => $user
    ]);
}

}
