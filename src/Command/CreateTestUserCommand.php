<?php
// src/Command/CreateTestUserCommand.php

namespace App\Command;

use App\Entity\ConfidentialiteUtilisateur;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Crée un utilisateur de test avec tous les paramètres nécessaires',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('Création d\'un utilisateur de test');

        // Demander les informations
        $prenomQuestion = new Question('Prénom (défaut: Test): ', 'Test');
        $prenom = $helper->ask($input, $output, $prenomQuestion);

        $nomQuestion = new Question('Nom (défaut: User): ', 'User');
        $nom = $helper->ask($input, $output, $nomQuestion);

        $emailQuestion = new Question('Email (défaut: test@example.com): ', 'test@example.com');
        $email = $helper->ask($input, $output, $emailQuestion);

        // Vérifier si l'email existe déjà
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->error("Un utilisateur avec l'email $email existe déjà.");
            return Command::FAILURE;
        }

        $passwordQuestion = new Question('Mot de passe (défaut: password): ', 'password');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $passwordQuestion);

        try {
            // Créer l'utilisateur
            $user = new User();
            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setTelephone('+216 12 345 678');
            $user->setStatut('actif');
            $user->setRoles(['ROLE_CLIENT']);

            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Créer les paramètres de confidentialité
            $confidentialite = new ConfidentialiteUtilisateur();
            $confidentialite->setUtilisateur($user);
            $confidentialite->setPartageDonnees(false);
            $confidentialite->setNotificationsEmail(true);
            $confidentialite->setVisibiliteProfil('prive');

            // Persister
            $this->entityManager->persist($user);
            $this->entityManager->persist($confidentialite);
            $this->entityManager->flush();

            $io->success([
                'Utilisateur créé avec succès !',
                '',
                "ID: {$user->getId()}",
                "Nom complet: {$user->getFullName()}",
                "Email: {$user->getEmail()}",
                "Mot de passe: $password",
                '',
                'Vous pouvez maintenant vous connecter avec ces identifiants.'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'Erreur lors de la création de l\'utilisateur:',
                $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }
}