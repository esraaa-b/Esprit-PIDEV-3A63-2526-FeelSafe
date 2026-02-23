<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222212806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add report_status column to publication and commentaire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication ADD report_status VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD report_status VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication DROP report_status');
        $this->addSql('ALTER TABLE commentaire DROP report_status');
    }
}
