<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222170030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure publication has report_reason, report_description, reported_at, is_reported columns (idempotent)';
    }

    public function up(Schema $schema): void
    {
        // MySQL 8.0 supports IF NOT EXISTS with ADD COLUMN
        $this->addSql("ALTER TABLE publication ADD COLUMN IF NOT EXISTS report_reason VARCHAR(50) DEFAULT NULL");
        $this->addSql("ALTER TABLE publication ADD COLUMN IF NOT EXISTS report_description LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE publication ADD COLUMN IF NOT EXISTS reported_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE publication ADD COLUMN IF NOT EXISTS is_reported TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE publication DROP COLUMN IF EXISTS report_reason");
        $this->addSql("ALTER TABLE publication DROP COLUMN IF EXISTS report_description");
        $this->addSql("ALTER TABLE publication DROP COLUMN IF EXISTS reported_at");
        $this->addSql("ALTER TABLE publication DROP COLUMN IF EXISTS is_reported");
    }
}
