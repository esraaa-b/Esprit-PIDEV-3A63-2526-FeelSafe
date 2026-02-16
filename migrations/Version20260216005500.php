<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216005500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create commentaire_like table for comment votes (like/dislike)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS commentaire_like (
            id INT AUTO_INCREMENT NOT NULL,
            commentaire_id INT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(10) NOT NULL,
            INDEX IDX_459B84E1BA9CD190 (commentaire_id),
            INDEX IDX_459B84E1A76ED395 (user_id),
            UNIQUE INDEX unique_user_commentaire_vote (commentaire_id, user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE commentaire_like 
            ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id)');
        $this->addSql('ALTER TABLE commentaire_like 
            ADD CONSTRAINT FK_459B84E1A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS commentaire_like');
    }
}
