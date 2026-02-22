<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215192705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE urgence DROP FOREIGN KEY fk_urgence_user');
        $this->addSql('ALTER TABLE urgence CHANGE user_id user_id INT NOT NULL');
        $this->addSql('DROP INDEX fk_urgence_user ON urgence');
        $this->addSql('CREATE INDEX IDX_737D6BCDA76ED395 ON urgence (user_id)');
        $this->addSql('ALTER TABLE urgence ADD CONSTRAINT fk_urgence_user FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE urgence DROP FOREIGN KEY FK_737D6BCDA76ED395');
        $this->addSql('ALTER TABLE urgence CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_737d6bcda76ed395 ON urgence');
        $this->addSql('CREATE INDEX fk_urgence_user ON urgence (user_id)');
        $this->addSql('ALTER TABLE urgence ADD CONSTRAINT FK_737D6BCDA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
    }
}
