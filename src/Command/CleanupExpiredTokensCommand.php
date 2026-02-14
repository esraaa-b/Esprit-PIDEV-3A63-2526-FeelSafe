<?php

namespace App\Command;

use App\Service\PasswordResetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-expired-tokens',
    description: 'Nettoie les tokens de réinitialisation expirés',
)]
class CleanupExpiredTokensCommand extends Command
{
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des tokens expirés');

        try {
            $deletedCount = $this->passwordResetService->cleanupExpiredTokens();
            
            $io->success(sprintf(
                '%d token(s) expiré(s) supprimé(s)',
                $deletedCount
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}