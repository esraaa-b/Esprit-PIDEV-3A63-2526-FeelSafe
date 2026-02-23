<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add chat_message table to store chatbot conversations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, conversation_id VARCHAR(100) NOT NULL, role VARCHAR(50) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_CHAT_MESSAGE_UTILISATEUR (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_CHAT_MESSAGE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_CHAT_MESSAGE_UTILISATEUR');
        $this->addSql('DROP TABLE chat_message');
    }
}
