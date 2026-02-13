<?php

namespace App\Command;

use App\Entity\ConfidentialiteUtilisateur;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-confidentialite',
    description: 'Crée les paramètres de confidentialité pour tous les utilisateurs qui n\'en ont pas',
)]
class CreateConfidentialiteCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des paramètres de confidentialité');

        $users = $this->userRepository->findAll();
        $totalUsers = count($users);
        $createdCount = 0;
        $skippedCount = 0;

        $io->progressStart($totalUsers);

        foreach ($users as $user) {
            if (!$user->getConfidentialite()) {
                $confidentialite = new ConfidentialiteUtilisateur();
                $confidentialite->setUtilisateur($user);
                $confidentialite->setPartageDonnees(false);
                $confidentialite->setNotificationsEmail(true);
                $confidentialite->setVisibiliteProfil('prive');
                
                $this->entityManager->persist($confidentialite);
                $createdCount++;
            } else {
                $skippedCount++;
            }
            
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->newLine(2);
        $io->success([
            "Opération terminée avec succès !",
            "Total d'utilisateurs: $totalUsers",
            "Paramètres créés: $createdCount",
            "Déjà existants: $skippedCount"
        ]);

        return Command::SUCCESS;
    }
}