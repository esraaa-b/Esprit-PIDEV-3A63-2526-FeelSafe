<?php

namespace App\Command;

use App\Entity\JournalEmotionnel;
use App\Entity\Utilisateur;
use App\Service\JournalReminderEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends a reminder email to users who haven't journaled in 2+ days.
 *
 * Run manually:
 *   php bin/console app:journal:send-reminders
 *
 * Schedule daily via cron (e.g. 9 AM):
 *   0 9 * * * cd /path/to/symfony && php bin/console app:journal:send-reminders >> var/log/reminders.log 2>&1
 */
#[AsCommand(
    name: 'app:journal:send-reminders',
    description: 'Send reminder emails to users who have not journaled in 2+ days'
)]
class SendJournalRemindersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface      $em,
        private JournalReminderEmailService $reminderService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $twoDaysAgo = new \DateTimeImmutable('-2 days');
        $now        = new \DateTimeImmutable();

        // Get all active users
        $users = $this->em->getRepository(Utilisateur::class)->createQueryBuilder('u')
        ->where('u.role LIKE :clientRole')
        ->setParameter('clientRole', '%ROLE_CLIENT%')
        ->getQuery()
        ->getResult();

        $sent   = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if (!$user->getEmail()) {
                continue;
            }

            // Find the most recent journal entry for this user
            $lastEntry = $this->em->getRepository(JournalEmotionnel::class)
                ->createQueryBuilder('j')
                ->where('j.utilisateur = :user')
                ->setParameter('user', $user)
                ->orderBy('j.dateCreation', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // Send reminder if:
            // - user has never journaled, OR
            // - last entry was more than 2 days ago
            $shouldSend = $lastEntry === null
                || $lastEntry->getDateCreation() < $twoDaysAgo;

            if ($shouldSend) {
                $this->reminderService->sendReminderEmail($user);
                $output->writeln("✅ Sent to: " . $user->getEmail());
                $sent++;
            } else {
                $skipped++;
            }
        }

        $output->writeln("Done — {$sent} sent, {$skipped} skipped.");
        return Command::SUCCESS;
    }
}