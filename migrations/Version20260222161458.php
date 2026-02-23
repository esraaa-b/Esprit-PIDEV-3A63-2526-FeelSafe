<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222161458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication ADD report_reason VARCHAR(50) DEFAULT NULL, ADD report_description LONGTEXT DEFAULT NULL, ADD reported_at DATETIME DEFAULT NULL, ADD is_reported TINYINT(1) NOT NULL, CHANGE contenu contenu LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication DROP report_reason, DROP report_description, DROP reported_at, DROP is_reported, CHANGE contenu contenu LONGTEXT NOT NULL');
    }
}
