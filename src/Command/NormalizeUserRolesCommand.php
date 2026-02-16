<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:normalize-roles',
    description: 'Normalise la colonne utilisateur.role en JSON (ex.: [\"ROLE_CLIENT\"])'
)]
class NormalizeUserRolesCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Normalisation des rôles (colonne utilisateur.role)');

        try {
            $rows = $this->connection->fetchAllAssociative('SELECT id, role FROM utilisateur');
        } catch (\Throwable $e) {
            $io->error('Impossible de lire la table utilisateur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $updated = 0;
        foreach ($rows as $row) {
            $id = $row['id'];
            $raw = (string) $row['role'];

            $normalized = $this->normalizeRoleValue($raw);

            if ($normalized !== null && $normalized !== $raw) {
                try {
                    $this->connection->executeStatement(
                        'UPDATE utilisateur SET role = :role WHERE id = :id',
                        ['role' => $normalized, 'id' => $id]
                    );
                    $updated++;
                } catch (\Throwable $e) {
                    $io->warning(sprintf('Échec mise à jour id=%d: %s', $id, $e->getMessage()));
                }
            }
        }

        $io->success(sprintf('Normalisation terminée. Lignes mises à jour: %d', $updated));
        return Command::SUCCESS;
    }

    private function normalizeRoleValue(string $raw): ?string
    {
        $rawTrim = trim($raw);

        $decoded = json_decode($rawTrim, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                return null;
            }
            if (is_string($decoded)) {
                $roles = [$this->mapToRole($decoded)];
                return json_encode(array_values(array_unique($roles)), JSON_UNESCAPED_UNICODE);
            }
        }

        $s = trim($rawTrim, " \t\n\r\0\x0B\"'");
        $role = $this->mapToRole($s);
        return json_encode([$role], JSON_UNESCAPED_UNICODE);
    }

    private function mapToRole(string $value): string
    {
        $v = strtoupper($value);
        $v = str_replace(['"', "'"], '', $v);
        $v = trim($v);

        if (str_starts_with($v, 'ROLE_')) {
            return $v;
        }
        return match ($v) {
            'ADMIN' => 'ROLE_ADMIN',
            'PROFESSIONNEL', 'PRO', 'PROF' => 'ROLE_PROFESSIONNEL',
            default => 'ROLE_CLIENT',
        };
    }
}

