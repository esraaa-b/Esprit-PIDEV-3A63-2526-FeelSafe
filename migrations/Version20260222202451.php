<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222202451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add report fields to commentaire table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire ADD report_reason VARCHAR(50) DEFAULT NULL, ADD report_description LONGTEXT DEFAULT NULL, ADD reported_at DATETIME DEFAULT NULL, ADD is_reported TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP report_reason, DROP report_description, DROP reported_at, DROP is_reported');
    }
}
