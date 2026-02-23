<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audio_url and transcription columns to publication table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication ADD audio_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE publication ADD transcription LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication DROP audio_url');
        $this->addSql('ALTER TABLE publication DROP transcription');
    }
}
