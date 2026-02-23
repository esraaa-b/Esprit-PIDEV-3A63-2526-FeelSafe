<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add translation_cache table for translation cache';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE translation_cache (
            id INT AUTO_INCREMENT NOT NULL,
            source_text_hash VARCHAR(64) NOT NULL,
            target_lang VARCHAR(10) NOT NULL,
            translated_text LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX source_target_idx (source_text_hash, target_lang),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE translation_cache');
    }
}
