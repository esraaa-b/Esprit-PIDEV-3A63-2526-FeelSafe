<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216124137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire_like (id INT AUTO_INCREMENT NOT NULL, commentaire_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(10) NOT NULL, INDEX IDX_459B84E1BA9CD190 (commentaire_id), INDEX IDX_459B84E1A76ED395 (user_id), UNIQUE INDEX unique_user_commentaire_vote (commentaire_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id)');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE accompagnement MODIFY id_accompagnement INT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B50EAE44');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B7DFA67FD');
        $this->addSql('DROP INDEX IDX_2130A05B7DFA67FD ON accompagnement');
        $this->addSql('DROP INDEX IDX_2130A05B50EAE44 ON accompagnement');
        $this->addSql('DROP INDEX `primary` ON accompagnement');
        $this->addSql('ALTER TABLE accompagnement ADD rendezvous_id INT DEFAULT NULL, ADD utilisateur_id INT DEFAULT NULL, DROP id_rendez_vous, DROP id_utilisateur, CHANGE id_accompagnement id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B3345E0A3 FOREIGN KEY (rendezvous_id) REFERENCES rendez_vous (id)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05BFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_2130A05B3345E0A3 ON accompagnement (rendezvous_id)');
        $this->addSql('CREATE INDEX IDX_2130A05BFB88E14F ON accompagnement (utilisateur_id)');
        $this->addSql('ALTER TABLE accompagnement ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE commentaire ADD parent_id INT DEFAULT NULL, ADD gif_url VARCHAR(500) DEFAULT NULL, ADD likes_count INT NOT NULL, ADD dislikes_count INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC727ACA70 FOREIGN KEY (parent_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_67F068BC727ACA70 ON commentaire (parent_id)');
        $this->addSql('ALTER TABLE publication ADD notification_message VARCHAR(255) DEFAULT NULL, ADD notification_read TINYINT(1) NOT NULL, ADD is_deleted TINYINT(1) NOT NULL, ADD notification_date DATETIME DEFAULT NULL, ADD categorie VARCHAR(255) DEFAULT NULL, ADD pinned_at DATETIME DEFAULT NULL, ADD likes_count INT NOT NULL, ADD dislikes_count INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous MODIFY id_rendez_vous INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A50EAE44');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AC400106A');
        $this->addSql('DROP INDEX IDX_65E8AA0AC400106A ON rendez_vous');
        $this->addSql('DROP INDEX IDX_65E8AA0A50EAE44 ON rendez_vous');
        $this->addSql('DROP INDEX `primary` ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous ADD utilisateur_id INT DEFAULT NULL, ADD professionnel_id INT DEFAULT NULL, DROP id_utilisateur, DROP id_professionnel, CHANGE id_rendez_vous id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8A49CC82 FOREIGN KEY (professionnel_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0AFB88E14F ON rendez_vous (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0A8A49CC82 ON rendez_vous (professionnel_id)');
        $this->addSql('ALTER TABLE rendez_vous ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE utilisateur ADD google_id VARCHAR(255) DEFAULT NULL, ADD github_id VARCHAR(255) DEFAULT NULL, ADD avatar VARCHAR(500) DEFAULT NULL, CHANGE mot_de_passe mot_de_passe VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1BA9CD190');
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1A76ED395');
        $this->addSql('DROP TABLE commentaire_like');
        $this->addSql('ALTER TABLE accompagnement MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B3345E0A3');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05BFB88E14F');
        $this->addSql('DROP INDEX IDX_2130A05B3345E0A3 ON accompagnement');
        $this->addSql('DROP INDEX IDX_2130A05BFB88E14F ON accompagnement');
        $this->addSql('DROP INDEX `PRIMARY` ON accompagnement');
        $this->addSql('ALTER TABLE accompagnement ADD id_rendez_vous INT DEFAULT NULL, ADD id_utilisateur INT DEFAULT NULL, DROP rendezvous_id, DROP utilisateur_id, CHANGE id id_accompagnement INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B7DFA67FD FOREIGN KEY (id_rendez_vous) REFERENCES rendez_vous (id_rendez_vous)');
        $this->addSql('CREATE INDEX IDX_2130A05B7DFA67FD ON accompagnement (id_rendez_vous)');
        $this->addSql('CREATE INDEX IDX_2130A05B50EAE44 ON accompagnement (id_utilisateur)');
        $this->addSql('ALTER TABLE accompagnement ADD PRIMARY KEY (id_accompagnement)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC727ACA70');
        $this->addSql('DROP INDEX IDX_67F068BC727ACA70 ON commentaire');
        $this->addSql('ALTER TABLE commentaire DROP parent_id, DROP gif_url, DROP likes_count, DROP dislikes_count');
        $this->addSql('ALTER TABLE publication DROP notification_message, DROP notification_read, DROP is_deleted, DROP notification_date, DROP categorie, DROP pinned_at, DROP likes_count, DROP dislikes_count');
        $this->addSql('ALTER TABLE rendez_vous MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AFB88E14F');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8A49CC82');
        $this->addSql('DROP INDEX IDX_65E8AA0AFB88E14F ON rendez_vous');
        $this->addSql('DROP INDEX IDX_65E8AA0A8A49CC82 ON rendez_vous');
        $this->addSql('DROP INDEX `PRIMARY` ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous ADD id_utilisateur INT DEFAULT NULL, ADD id_professionnel INT DEFAULT NULL, DROP utilisateur_id, DROP professionnel_id, CHANGE id id_rendez_vous INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AC400106A FOREIGN KEY (id_professionnel) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0AC400106A ON rendez_vous (id_professionnel)');
        $this->addSql('CREATE INDEX IDX_65E8AA0A50EAE44 ON rendez_vous (id_utilisateur)');
        $this->addSql('ALTER TABLE rendez_vous ADD PRIMARY KEY (id_rendez_vous)');
        $this->addSql('ALTER TABLE utilisateur DROP google_id, DROP github_id, DROP avatar, CHANGE mot_de_passe mot_de_passe VARCHAR(255) NOT NULL');
    }
}
