<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\JournalEmotionnelType;
use App\Enum\EmotionEnum;


class JournalController extends AbstractController
{
   
    #[Route('/dashboard/journal', name: 'app_journal')]
public function index(EntityManagerInterface $em): Response
{
    $journals = $em->getRepository(JournalEmotionnel::class)
        ->findBy(
            ['utilisateur' => $this->getUser()],
            ['dateCreation' => 'DESC']
        );

    return $this->render('dashboard/journal/index.html.twig', [
        'journals' => $journals,
         'moodOptions' => EmotionEnum::cases(),
    ]);
}

    #[Route('/dashboard/journal/new', name: 'app_journal_new', methods: ['POST'])]
public function new(
    Request $request,
    EntityManagerInterface $em
): Response {
    $journal = new JournalEmotionnel();

   $journal->setEmotion(
    EmotionEnum::from($request->request->get('emotion'))
);
    $journal->setContenu($request->request->get('contenu'));
    $journal->setDateCreation(new \DateTime());
    $journal->setUtilisateur($this->getUser());

    $em->persist($journal);
    $em->flush();

    return $this->redirectToRoute('app_journal');
}
#[Route('/dashboard/journal/{id}/edit', name: 'app_journal_edit')]
public function edit(
    JournalEmotionnel $journal,
    Request $request,
    EntityManagerInterface $em
): Response {
    $this->denyAccessUnlessGranted('OWNER', $journal);

    if ($request->isMethod('POST')) {
        $journal->setEmotion(
            EmotionEnum::from($request->request->get('emotion'))
        );
        $journal->setContenu($request->request->get('contenu'));

        $em->flush();

        return $this->redirectToRoute('app_journal');
    }

    return $this->render('dashboard/journal/edit.html.twig', [
        'journal' => $journal,
         'moodOptions' => EmotionEnum::cases(),
    ]);
}
#[Route('/dashboard/journal/{id}/delete', name: 'app_journal_delete', methods: ['POST'])]
public function delete(
    JournalEmotionnel $journal,
    EntityManagerInterface $em
): Response {
    $this->denyAccessUnlessGranted('OWNER', $journal);

    $em->remove($journal);
    $em->flush();

    return $this->redirectToRoute('app_journal');
}


}