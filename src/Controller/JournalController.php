<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Enum\EmotionEnum;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class JournalController extends AbstractController
{
    private $params;
    
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }
   
    #[Route('/dashboard/journal', name: 'app_journal')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $journals = $em->getRepository(JournalEmotionnel::class)
            ->findBy(
                ['utilisateur' => $user],
                ['dateCreation' => 'DESC']
            );

        // Calcul des statistiques
        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');
        
        // Statistiques du mois
        $monthEntries = $em->getRepository(JournalEmotionnel::class)
            ->createQueryBuilder('j')
            ->where('j.utilisateur = :user')
            ->andWhere('j.dateCreation BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $firstDayOfMonth)
            ->setParameter('end', $lastDayOfMonth)
            ->getQuery()
            ->getResult();
        
        $monthCount = count($monthEntries);
        
        // Jours consécutifs (simplifié - dernière semaine)
        $weekAgo = new \DateTime('-7 days');
        $weekEntries = $em->getRepository(JournalEmotionnel::class)
            ->createQueryBuilder('j')
            ->where('j.utilisateur = :user')
            ->andWhere('j.dateCreation >= :weekAgo')
            ->setParameter('user', $user)
            ->setParameter('weekAgo', $weekAgo)
            ->orderBy('j.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
        
        $consecutiveDays = 0;
        $currentDate = new \DateTime();
        $hasEntryToday = false;
        
        foreach ($weekEntries as $entry) {
            if ($entry->getDateCreation()->format('Y-m-d') === $currentDate->format('Y-m-d')) {
                $hasEntryToday = true;
                break;
            }
        }
        
        if ($hasEntryToday) {
            $consecutiveDays = 1;
            $checkDate = clone $currentDate;
            $checkDate->modify('-1 day');
            
            while (true) {
                $found = false;
                foreach ($weekEntries as $entry) {
                    if ($entry->getDateCreation()->format('Y-m-d') === $checkDate->format('Y-m-d')) {
                        $consecutiveDays++;
                        $checkDate->modify('-1 day');
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        
        // Humeur positive (pourcentage)
        $positiveMoods = [EmotionEnum::TRES_BIEN, EmotionEnum::BIEN];
        $totalEntries = count($journals);
        $positiveCount = 0;
        
        foreach ($journals as $journal) {
            if (in_array($journal->getEmotion(), $positiveMoods)) {
                $positiveCount++;
            }
        }
        
        $positivePercentage = $totalEntries > 0 ? round(($positiveCount / $totalEntries) * 100) : 0;
        
        // Réactions reçues (simulé pour l'exemple)
        $reactionsCount = $totalEntries * rand(3, 8);
        
        // Statistiques par émotion
        $moodStats = [];
        foreach (EmotionEnum::cases() as $mood) {
            $count = $em->getRepository(JournalEmotionnel::class)
                ->createQueryBuilder('j')
                ->select('COUNT(j.id)')
                ->where('j.utilisateur = :user')
                ->andWhere('j.emotion = :emotion')
                ->setParameter('user', $user)
                ->setParameter('emotion', $mood)
                ->getQuery()
                ->getSingleScalarResult();
            
            $moodStats[$mood->value] = $count;
        }
        
        // Données pour le graphique (30 derniers jours)
        $chartLabels = [];
        $chartData = [
            'tres_bien' => [],
            'bien' => [],
            'neutre' => [],
            'pas_bien' => [],
            'tres_mal' => []
        ];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $chartLabels[] = $date->format('d/m');
            
            $dayEntries = $em->getRepository(JournalEmotionnel::class)
                ->createQueryBuilder('j')
                ->where('j.utilisateur = :user')
                ->andWhere('j.dateCreation >= :start')
                ->andWhere('j.dateCreation < :end')
                ->setParameter('user', $user)
                ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                ->getQuery()
                ->getResult();
            
            foreach (EmotionEnum::cases() as $mood) {
                $chartData[$mood->value][] = 0;
            }
            
            foreach ($dayEntries as $entry) {
                $moodValue = $entry->getEmotion()->value;
                $chartData[$moodValue][count($chartData[$moodValue]) - 1]++;
            }
        }

        return $this->render('dashboard/journal/index.html.twig', [
            'journals' => $journals,
            'moodOptions' => EmotionEnum::cases(),
            'stats' => [
                'monthEntries' => $monthCount,
                'consecutiveDays' => $consecutiveDays,
                'positivePercentage' => $positivePercentage,
                'reactionsCount' => $reactionsCount
            ],
            'moodStats' => $moodStats,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData
        ]);
    }

    #[Route('/dashboard/journal/new', name: 'app_journal_new', methods: ['POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isCsrfTokenValid('create_journal', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $journal = new JournalEmotionnel();

        // Émotion
        $emotion = $request->request->get('emotion');
        if (!$emotion) {
            $this->addFlash('error', 'Veuillez sélectionner une émotion.');
            return $this->redirectToRoute('app_journal');
        }
        $journal->setEmotion(EmotionEnum::from($emotion));
        $journal->setUtilisateur($user);
        $journal->setDateCreation(new \DateTime());

        // Contenu, image, audio
        $content = trim((string) $request->request->get('contenu'));
        $imageFile = $request->files->get('image');
        $audioFile = $request->files->get('audio');

        // Vérifier qu'au moins un élément est présent
        if (empty($content) && !$imageFile && !$audioFile) {
            $this->addFlash('error', 'Ajoutez du texte, une image ou un audio.');
            return $this->redirectToRoute('app_journal');
        }

        // Vérifier la longueur du texte si présent
        if (!empty($content) && mb_strlen($content) < 6) {
            $this->addFlash('error', 'Le texte doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_journal');
        }

        $journal->setContenu($content ?: null);

        // Upload directories
        $uploadDir = $this->params->get('kernel.project_dir') . '/public/uploads/journals';
        if (!is_dir($uploadDir . '/images')) {
            mkdir($uploadDir . '/images', 0777, true);
        }
        if (!is_dir($uploadDir . '/audio')) {
            mkdir($uploadDir . '/audio', 0777, true);
        }

        // Image upload
        if ($imageFile) {
            $ext = $imageFile->guessExtension() ?: $imageFile->getClientOriginalExtension() ?: 'jpg';
            $name = uniqid() . '.' . $ext;
            $imageFile->move($uploadDir . '/images', $name);
            $journal->setImage($name);
        }

        // Audio upload
        if ($audioFile) {
            $ext = $audioFile->guessExtension() ?: $audioFile->getClientOriginalExtension() ?: 'mp3';
            $name = uniqid() . '.' . $ext;
            $audioFile->move($uploadDir . '/audio', $name);
            $journal->setAudio($name);
        }

        $em->persist($journal);
        $em->flush();

        $this->addFlash('success', 'Entrée de journal ajoutée avec succès !');
        return $this->redirectToRoute('app_journal');
    }

    #[Route('/dashboard/journal/{id}/edit', name: 'app_journal_edit', methods: ['POST'])]
    public function edit(
        Request $request,
        JournalEmotionnel $journal,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('OWNER', $journal);

        if (!$this->isCsrfTokenValid('edit_journal_' . $journal->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        // Émotion
        if ($emotion = $request->request->get('emotion')) {
            $journal->setEmotion(EmotionEnum::from($emotion));
        }

        // Contenu
        $content = trim((string) $request->request->get('contenu'));
        $imageFile = $request->files->get('image');
        $audioFile = $request->files->get('audio');
        $removeImage = $request->request->get('remove_image');
        $removeAudio = $request->request->get('remove_audio');

        // Vérifier qu'au moins un élément sera présent après modification
        $hasContent = !empty($content);
        $hasImage = $journal->getImage() && $removeImage !== '1' || $imageFile;
        $hasAudio = $journal->getAudio() && $removeAudio !== '1' || $audioFile;

        if (!$hasContent && !$hasImage && !$hasAudio) {
            $this->addFlash('error', 'Le journal doit contenir du texte, une image ou un audio.');
            return $this->redirectToRoute('app_journal');
        }

        // Vérifier la longueur du texte si présent
        if (!empty($content) && mb_strlen($content) < 6) {
            $this->addFlash('error', 'Le texte doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_journal');
        }

        $journal->setContenu($content ?: null);

        $uploadDir = $this->params->get('kernel.project_dir') . '/public/uploads/journals';

        // Créer les dossiers si nécessaire
        if (!is_dir($uploadDir . '/images')) {
            mkdir($uploadDir . '/images', 0777, true);
        }
        if (!is_dir($uploadDir . '/audio')) {
            mkdir($uploadDir . '/audio', 0777, true);
        }

        // GESTION DE L'IMAGE
        if ($removeImage === '1') {
            if ($journal->getImage()) {
                @unlink($uploadDir . '/images/' . $journal->getImage());
                $journal->setImage(null);
            }
        } elseif ($imageFile) {
            if ($journal->getImage()) {
                @unlink($uploadDir . '/images/' . $journal->getImage());
            }
            $ext = $imageFile->guessExtension() ?: 'jpg';
            $name = uniqid() . '.' . $ext;
            $imageFile->move($uploadDir . '/images', $name);
            $journal->setImage($name);
        }

        // GESTION DE L'AUDIO
        if ($removeAudio === '1') {
            if ($journal->getAudio()) {
                @unlink($uploadDir . '/audio/' . $journal->getAudio());
                $journal->setAudio(null);
            }
        } elseif ($audioFile) {
            if ($journal->getAudio()) {
                @unlink($uploadDir . '/audio/' . $journal->getAudio());
            }
            $mime = $audioFile->getMimeType();
            $ext = match ($mime) {
                'audio/webm' => 'webm',
                'audio/ogg' => 'ogg',
                'audio/mpeg' => 'mp3',
                default => 'webm',
            };
            $name = uniqid('audio_') . '.' . $ext;
            $audioFile->move($uploadDir . '/audio', $name);
            $journal->setAudio($name);
        }

        $em->flush();

        $this->addFlash('success', 'Entrée modifiée avec succès !');
        return $this->redirectToRoute('app_journal');
    }

    #[Route('/dashboard/journal/{id}/delete', name: 'app_journal_delete', methods: ['POST'])]
    public function delete(
        JournalEmotionnel $journal,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('OWNER', $journal);

        $uploadDir = $this->params->get('kernel.project_dir') . '/public/uploads/journals';

        // Supprimer l'image
        if ($journal->getImage()) {
            $imagePath = $uploadDir . '/images/' . $journal->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Supprimer l'audio
        if ($journal->getAudio()) {
            $audioPath = $uploadDir . '/audio/' . $journal->getAudio();
            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
        }

        $em->remove($journal);
        $em->flush();

        $this->addFlash('success', 'Entrée supprimée avec succès !');
        return $this->redirectToRoute('app_journal');
    }
}