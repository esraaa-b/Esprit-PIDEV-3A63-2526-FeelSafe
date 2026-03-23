<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228201039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire CHANGE parent_id parent_id INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1BA9CD190');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FFB88E14F');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire CHANGE parent_id parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id)');
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1BA9CD190');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id)');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FFB88E14F');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }
}
