<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215160933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activite_bien_etre (id INT AUTO_INCREMENT NOT NULL, cree_par_id INT DEFAULT NULL, nom_activite VARCHAR(200) NOT NULL, type_activite VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, duree_suggeree INT DEFAULT NULL, categorie VARCHAR(100) DEFAULT NULL, niveau_difficulte VARCHAR(20) DEFAULT NULL, objectif_emotionnel VARCHAR(200) DEFAULT NULL, est_predefinie TINYINT(1) DEFAULT NULL, est_active TINYINT(1) DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, instructions_detaillees LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, INDEX IDX_362E6BCBFC29C013 (cree_par_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, is_used TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_6B7BA4B65F37A13B (token), INDEX IDX_6B7BA4B6A76ED395 (user_id), INDEX idx_token (token), INDEX idx_expires_at (expires_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session_activite (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, activite_id INT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, duree_reelle INT DEFAULT NULL, statut_session VARCHAR(20) NOT NULL, humeur_avant VARCHAR(20) DEFAULT NULL, score_humeur_avant SMALLINT DEFAULT NULL, emotion_avant VARCHAR(100) DEFAULT NULL, humeur_apres VARCHAR(20) DEFAULT NULL, score_humeur_apres SMALLINT DEFAULT NULL, emotion_apres VARCHAR(100) DEFAULT NULL, note_satisfaction SMALLINT DEFAULT NULL, commentaire VARCHAR(500) DEFAULT NULL, impact_percu VARCHAR(20) DEFAULT NULL, est_objectif_atteint TINYINT(1) DEFAULT NULL, frequence_souhaitee VARCHAR(20) DEFAULT NULL, recommande_par VARCHAR(20) DEFAULT NULL, score_recommandation_ia NUMERIC(3, 2) DEFAULT NULL, INDEX IDX_33D9357DFB88E14F (utilisateur_id), INDEX IDX_33D9357D9B0F88B1 (activite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activite_bien_etre ADD CONSTRAINT FK_362E6BCBFC29C013 FOREIGN KEY (cree_par_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357D9B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite_bien_etre (id)');
        $this->addSql('ALTER TABLE accompagnement MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B3345E0A3');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05BFB88E14F');
        $this->addSql('DROP INDEX IDX_2130A05BFB88E14F ON accompagnement');
        $this->addSql('DROP INDEX IDX_2130A05B3345E0A3 ON accompagnement');
        $this->addSql('DROP INDEX `primary` ON accompagnement');
        $this->addSql('ALTER TABLE accompagnement ADD id_rendez_vous INT DEFAULT NULL, ADD id_utilisateur INT DEFAULT NULL, DROP rendezvous_id, DROP utilisateur_id, CHANGE id id_accompagnement INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B7DFA67FD FOREIGN KEY (id_rendez_vous) REFERENCES rendez_vous (id_rendez_vous)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_2130A05B7DFA67FD ON accompagnement (id_rendez_vous)');
        $this->addSql('CREATE INDEX IDX_2130A05B50EAE44 ON accompagnement (id_utilisateur)');
        $this->addSql('ALTER TABLE accompagnement ADD PRIMARY KEY (id_accompagnement)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur CHANGE utilisateur_id utilisateur_id INT DEFAULT NULL, CHANGE partage_donnees partage_donnees TINYINT(1) NOT NULL, CHANGE notifications_email notifications_email TINYINT(1) NOT NULL, CHANGE visibilite_profil visibilite_profil VARCHAR(20) NOT NULL, CHANGE date_modification date_modification DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE rendez_vous MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8A49CC82');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AFB88E14F');
        $this->addSql('DROP INDEX IDX_65E8AA0AFB88E14F ON rendez_vous');
        $this->addSql('DROP INDEX IDX_65E8AA0A8A49CC82 ON rendez_vous');
        $this->addSql('DROP INDEX `primary` ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous ADD id_utilisateur INT DEFAULT NULL, ADD id_professionnel INT DEFAULT NULL, DROP utilisateur_id, DROP professionnel_id, CHANGE id id_rendez_vous INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AC400106A FOREIGN KEY (id_professionnel) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0A50EAE44 ON rendez_vous (id_utilisateur)');
        $this->addSql('CREATE INDEX IDX_65E8AA0AC400106A ON rendez_vous (id_professionnel)');
        $this->addSql('ALTER TABLE rendez_vous ADD PRIMARY KEY (id_rendez_vous)');
        $this->addSql('ALTER TABLE utilisateur CHANGE email email VARCHAR(180) NOT NULL, CHANGE role role JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE date_creation date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite_bien_etre DROP FOREIGN KEY FK_362E6BCBFC29C013');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357DFB88E14F');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357D9B0F88B1');
        $this->addSql('DROP TABLE activite_bien_etre');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE session_activite');
        $this->addSql('ALTER TABLE accompagnement MODIFY id_accompagnement INT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B7DFA67FD');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B50EAE44');
        $this->addSql('DROP INDEX IDX_2130A05B7DFA67FD ON accompagnement');
        $this->addSql('DROP INDEX IDX_2130A05B50EAE44 ON accompagnement');
        $this->addSql('DROP INDEX `PRIMARY` ON accompagnement');
        $this->addSql('ALTER TABLE accompagnement ADD rendezvous_id INT NOT NULL, ADD utilisateur_id INT NOT NULL, DROP id_rendez_vous, DROP id_utilisateur, CHANGE id_accompagnement id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B3345E0A3 FOREIGN KEY (rendezvous_id) REFERENCES rendez_vous (id)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05BFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_2130A05BFB88E14F ON accompagnement (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_2130A05B3345E0A3 ON accompagnement (rendezvous_id)');
        $this->addSql('ALTER TABLE accompagnement ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur CHANGE utilisateur_id utilisateur_id INT NOT NULL, CHANGE partage_donnees partage_donnees TINYINT(1) DEFAULT NULL, CHANGE notifications_email notifications_email TINYINT(1) DEFAULT NULL, CHANGE visibilite_profil visibilite_profil TINYINT(1) DEFAULT NULL, CHANGE date_modification date_modification DATETIME NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rendez_vous MODIFY id_rendez_vous INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A50EAE44');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AC400106A');
        $this->addSql('DROP INDEX IDX_65E8AA0A50EAE44 ON rendez_vous');
        $this->addSql('DROP INDEX IDX_65E8AA0AC400106A ON rendez_vous');
        $this->addSql('DROP INDEX `PRIMARY` ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous ADD utilisateur_id INT NOT NULL, ADD professionnel_id INT NOT NULL, DROP id_utilisateur, DROP id_professionnel, CHANGE id_rendez_vous id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8A49CC82 FOREIGN KEY (professionnel_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0AFB88E14F ON rendez_vous (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0A8A49CC82 ON rendez_vous (professionnel_id)');
        $this->addSql('ALTER TABLE rendez_vous ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE email email VARCHAR(150) NOT NULL, CHANGE role role VARCHAR(30) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
    }
}
