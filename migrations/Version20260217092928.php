<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217092928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accompagnement (id INT AUTO_INCREMENT NOT NULL, rendezvous_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, prochain_rdv VARCHAR(5) NOT NULL, date_prochain_rdv DATE DEFAULT NULL, objectifs LONGTEXT DEFAULT NULL, notes_suivi LONGTEXT DEFAULT NULL, niveau_priorite SMALLINT DEFAULT NULL, INDEX IDX_2130A05B3345E0A3 (rendezvous_id), INDEX IDX_2130A05BFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE activite_bien_etre (id INT AUTO_INCREMENT NOT NULL, cree_par_id INT DEFAULT NULL, nom_activite VARCHAR(200) NOT NULL, type_activite VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, duree_suggeree INT DEFAULT NULL, categorie VARCHAR(100) DEFAULT NULL, niveau_difficulte VARCHAR(20) DEFAULT NULL, objectif_emotionnel VARCHAR(200) DEFAULT NULL, est_predefinie TINYINT(1) DEFAULT NULL, est_active TINYINT(1) DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, instructions_detaillees LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, INDEX IDX_362E6BCBFC29C013 (cree_par_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, publication_id INT NOT NULL, user_id INT NOT NULL, parent_id INT DEFAULT NULL, contenu LONGTEXT NOT NULL, gif_url VARCHAR(500) DEFAULT NULL, date_commentaire DATETIME DEFAULT NULL, likes_count INT NOT NULL, dislikes_count INT NOT NULL, INDEX IDX_67F068BC38B217A7 (publication_id), INDEX IDX_67F068BCA76ED395 (user_id), INDEX IDX_67F068BC727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commentaire_like (id INT AUTO_INCREMENT NOT NULL, commentaire_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(10) NOT NULL, INDEX IDX_459B84E1BA9CD190 (commentaire_id), INDEX IDX_459B84E1A76ED395 (user_id), UNIQUE INDEX unique_user_commentaire_vote (commentaire_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE confidentialite_utilisateur (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, partage_donnees TINYINT(1) NOT NULL, notifications_email TINYINT(1) NOT NULL, visibilite_profil VARCHAR(20) NOT NULL, date_modification DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_7AF1B92FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, urgence_id INT NOT NULL, intervention_type VARCHAR(50) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, result VARCHAR(50) DEFAULT NULL, intervention_date DATETIME DEFAULT NULL, INDEX IDX_D11814AB578B7FBD (urgence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal_emotionnel (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, emotion VARCHAR(20) NOT NULL, contenu LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, audio VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, INDEX IDX_443F70FFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, is_used TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_6B7BA4B65F37A13B (token), INDEX IDX_6B7BA4B6A76ED395 (user_id), INDEX idx_token (token), INDEX idx_expires_at (expires_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publication (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, notification_message VARCHAR(255) DEFAULT NULL, notification_read TINYINT(1) NOT NULL, is_deleted TINYINT(1) NOT NULL, notification_date DATETIME DEFAULT NULL, categorie VARCHAR(255) DEFAULT NULL, pinned_at DATETIME DEFAULT NULL, likes_count INT NOT NULL, dislikes_count INT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, date_publication DATETIME DEFAULT NULL, INDEX IDX_AF3C6779A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, professionnel_id INT DEFAULT NULL, date_rdv DATE NOT NULL, heure_rdv TIME NOT NULL, mode VARCHAR(20) NOT NULL, localisation VARCHAR(255) DEFAULT NULL, statut VARCHAR(20) NOT NULL, commentaire VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, INDEX IDX_65E8AA0AFB88E14F (utilisateur_id), INDEX IDX_65E8AA0A8A49CC82 (professionnel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session_activite (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, activite_id INT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, duree_reelle INT DEFAULT NULL, statut_session VARCHAR(20) NOT NULL, humeur_avant VARCHAR(20) DEFAULT NULL, score_humeur_avant SMALLINT DEFAULT NULL, emotion_avant VARCHAR(100) DEFAULT NULL, humeur_apres VARCHAR(20) DEFAULT NULL, score_humeur_apres SMALLINT DEFAULT NULL, emotion_apres VARCHAR(100) DEFAULT NULL, note_satisfaction SMALLINT DEFAULT NULL, commentaire VARCHAR(500) DEFAULT NULL, impact_percu VARCHAR(20) DEFAULT NULL, est_objectif_atteint TINYINT(1) DEFAULT NULL, frequence_souhaitee VARCHAR(20) DEFAULT NULL, recommande_par VARCHAR(20) DEFAULT NULL, score_recommandation_ia NUMERIC(3, 2) DEFAULT NULL, INDEX IDX_33D9357DFB88E14F (utilisateur_id), INDEX IDX_33D9357D9B0F88B1 (activite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tendance_emotionnelle (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, mois SMALLINT NOT NULL, annee INT NOT NULL, emotion VARCHAR(20) NOT NULL, totale_occurrences INT NOT NULL, pourcentage NUMERIC(5, 2) NOT NULL, date_calcul DATETIME NOT NULL, INDEX IDX_4D7A6A60FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE urgence (id INT AUTO_INCREMENT NOT NULL, type_urgence VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, severity_level SMALLINT DEFAULT NULL, status VARCHAR(30) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, role JSON NOT NULL COMMENT \'(DC2Type:json)\', mot_de_passe VARCHAR(255) DEFAULT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', google_id VARCHAR(255) DEFAULT NULL, github_id VARCHAR(255) DEFAULT NULL, avatar VARCHAR(500) DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B3345E0A3 FOREIGN KEY (rendezvous_id) REFERENCES rendez_vous (id)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05BFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE activite_bien_etre ADD CONSTRAINT FK_362E6BCBFC29C013 FOREIGN KEY (cree_par_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC727ACA70 FOREIGN KEY (parent_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id)');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur ADD CONSTRAINT FK_7AF1B92FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB578B7FBD FOREIGN KEY (urgence_id) REFERENCES urgence (id)');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8A49CC82 FOREIGN KEY (professionnel_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357D9B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite_bien_etre (id)');
        $this->addSql('ALTER TABLE tendance_emotionnelle ADD CONSTRAINT FK_4D7A6A60FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B3345E0A3');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05BFB88E14F');
        $this->addSql('ALTER TABLE activite_bien_etre DROP FOREIGN KEY FK_362E6BCBFC29C013');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA76ED395');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC727ACA70');
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1BA9CD190');
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1A76ED395');
        $this->addSql('ALTER TABLE confidentialite_utilisateur DROP FOREIGN KEY FK_7AF1B92FB88E14F');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB578B7FBD');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FFB88E14F');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779A76ED395');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AFB88E14F');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8A49CC82');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357DFB88E14F');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357D9B0F88B1');
        $this->addSql('ALTER TABLE tendance_emotionnelle DROP FOREIGN KEY FK_4D7A6A60FB88E14F');
        $this->addSql('DROP TABLE accompagnement');
        $this->addSql('DROP TABLE activite_bien_etre');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE commentaire_like');
        $this->addSql('DROP TABLE confidentialite_utilisateur');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE journal_emotionnel');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE session_activite');
        $this->addSql('DROP TABLE tendance_emotionnelle');
        $this->addSql('DROP TABLE urgence');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
