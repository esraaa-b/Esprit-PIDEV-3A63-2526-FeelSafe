<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208131821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE session_activite (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, duree_reelle INT DEFAULT NULL, statut_session VARCHAR(20) NOT NULL, humeur_avant VARCHAR(20) DEFAULT NULL, score_humeur_avant SMALLINT DEFAULT NULL, emotion_avant VARCHAR(100) DEFAULT NULL, humeur_apres VARCHAR(20) DEFAULT NULL, score_humeur_apres SMALLINT DEFAULT NULL, emotion_apres VARCHAR(100) DEFAULT NULL, note_satisfaction SMALLINT DEFAULT NULL, commentaire VARCHAR(500) DEFAULT NULL, impact_percu VARCHAR(20) DEFAULT NULL, est_objectif_atteint TINYINT(1) DEFAULT NULL, frequence_souhaitee VARCHAR(20) DEFAULT NULL, recommande_par VARCHAR(20) DEFAULT NULL, score_recommandation_ia NUMERIC(3, 2) DEFAULT NULL, utilisateur_id INT NOT NULL, activite_id INT NOT NULL, INDEX IDX_33D9357DFB88E14F (utilisateur_id), INDEX IDX_33D9357D9B0F88B1 (activite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(30) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357D9B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite_bien_etre (id)');
        $this->addSql('ALTER TABLE sessionactivite DROP FOREIGN KEY fk_session_activite');
        $this->addSql('ALTER TABLE sessionactivite DROP FOREIGN KEY fk_session_utilisateur');
        $this->addSql('DROP TABLE sessionactivite');
        $this->addSql('DROP TABLE utilisateurs');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY fk_accomp_user');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY fk_accomp_rdv');
        $this->addSql('DROP INDEX fk_accomp_rdv ON accompagnement');
        $this->addSql('DROP INDEX fk_accomp_user ON accompagnement');
        $this->addSql('ALTER TABLE accompagnement ADD rendezvous_id INT NOT NULL, ADD utilisateur_id INT NOT NULL, DROP id_rendez_vous, DROP id_utilisateur, CHANGE prochain_rdv prochain_rdv VARCHAR(5) NOT NULL, CHANGE date_prochain_rdv date_prochain_rdv DATE DEFAULT NULL, CHANGE objectifs objectifs LONGTEXT DEFAULT NULL, CHANGE notes_suivi notes_suivi LONGTEXT DEFAULT NULL, CHANGE niveau_priorite niveau_priorite SMALLINT DEFAULT NULL, CHANGE id_accompagnement id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B3345E0A3 FOREIGN KEY (rendezvous_id) REFERENCES rendez_vous (id)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05BFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_2130A05B3345E0A3 ON accompagnement (rendezvous_id)');
        $this->addSql('CREATE INDEX IDX_2130A05BFB88E14F ON accompagnement (utilisateur_id)');
        $this->addSql('ALTER TABLE activite_bien_etre DROP FOREIGN KEY fk_activite_createur');
        $this->addSql('DROP INDEX idx_activite_type ON activite_bien_etre');
        $this->addSql('DROP INDEX idx_activite_categorie ON activite_bien_etre');
        $this->addSql('DROP INDEX fk_activite_createur ON activite_bien_etre');
        $this->addSql('ALTER TABLE activite_bien_etre CHANGE description description LONGTEXT DEFAULT NULL, CHANGE categorie categorie VARCHAR(100) DEFAULT NULL, CHANGE niveau_difficulte niveau_difficulte VARCHAR(20) DEFAULT NULL, CHANGE objectif_emotionnel objectif_emotionnel VARCHAR(200) DEFAULT NULL, CHANGE est_predefinie est_predefinie TINYINT(1) DEFAULT NULL, CHANGE est_active est_active TINYINT(1) DEFAULT NULL, CHANGE image_url image_url VARCHAR(500) DEFAULT NULL, CHANGE instructions_detaillees instructions_detaillees LONGTEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE id_activite id INT AUTO_INCREMENT NOT NULL, CHANGE cree_par cree_par_id INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE activite_bien_etre ADD CONSTRAINT FK_362E6BCBFC29C013 FOREIGN KEY (cree_par_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_362E6BCBFC29C013 ON activite_bien_etre (cree_par_id)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_user');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_publication');
        $this->addSql('DROP INDEX fk_commentaire_publication ON commentaire');
        $this->addSql('DROP INDEX fk_commentaire_user ON commentaire');
        $this->addSql('ALTER TABLE commentaire ADD publication_id INT NOT NULL, ADD user_id INT NOT NULL, DROP id_publication, DROP id_user, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE date_commentaire date_commentaire DATETIME DEFAULT NULL, CHANGE id_commentaire id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_67F068BC38B217A7 ON commentaire (publication_id)');
        $this->addSql('CREATE INDEX IDX_67F068BCA76ED395 ON commentaire (user_id)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur DROP INDEX fk_conf_user, ADD UNIQUE INDEX UNIQ_7AF1B92FB88E14F (utilisateur_id)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur DROP FOREIGN KEY fk_conf_user');
        $this->addSql('ALTER TABLE confidentialite_utilisateur CHANGE partage_donnees partage_donnees TINYINT(1) DEFAULT NULL, CHANGE notifications_email notifications_email TINYINT(1) DEFAULT NULL, CHANGE visibilite_profil visibilite_profil TINYINT(1) DEFAULT NULL, CHANGE date_modification date_modification DATETIME NOT NULL');
        $this->addSql('ALTER TABLE confidentialite_utilisateur ADD CONSTRAINT FK_7AF1B92FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY fk_intervention_urgence');
        $this->addSql('ALTER TABLE intervention CHANGE intervention_type intervention_type VARCHAR(50) DEFAULT NULL, CHANGE notes notes VARCHAR(255) DEFAULT NULL, CHANGE result result VARCHAR(50) DEFAULT NULL, CHANGE intervention_date intervention_date DATETIME DEFAULT NULL, CHANGE id_intervention id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB578B7FBD FOREIGN KEY (urgence_id) REFERENCES urgence (id)');
        $this->addSql('ALTER TABLE intervention RENAME INDEX fk_intervention_urgence TO IDX_D11814AB578B7FBD');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY fk_journal_utilisateur');
        $this->addSql('DROP INDEX fk_journal_utilisateur ON journal_emotionnel');
        $this->addSql('ALTER TABLE journal_emotionnel ADD audio VARCHAR(255) DEFAULT NULL, CHANGE emotion emotion VARCHAR(20) NOT NULL, CHANGE contenu contenu LONGTEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE id_journal id INT AUTO_INCREMENT NOT NULL, CHANGE id_utilisateur utilisateur_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_443F70FFB88E14F ON journal_emotionnel (utilisateur_id)');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY fk_publication_user');
        $this->addSql('DROP INDEX fk_publication_user ON publication');
        $this->addSql('ALTER TABLE publication CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE date_publication date_publication DATETIME DEFAULT NULL, CHANGE id_publication id INT AUTO_INCREMENT NOT NULL, CHANGE id_user user_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_AF3C6779A76ED395 ON publication (user_id)');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY fk_rdv_utilisateur');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY fk_rdv_professionnel');
        $this->addSql('DROP INDEX fk_rdv_professionnel ON rendez_vous');
        $this->addSql('DROP INDEX fk_rdv_utilisateur ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous ADD utilisateur_id INT NOT NULL, ADD professionnel_id INT NOT NULL, DROP id_utilisateur, DROP id_professionnel, CHANGE mode mode VARCHAR(20) NOT NULL, CHANGE localisation localisation VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE commentaire commentaire VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE id_rendez_vous id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8A49CC82 FOREIGN KEY (professionnel_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0AFB88E14F ON rendez_vous (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_65E8AA0A8A49CC82 ON rendez_vous (professionnel_id)');
        $this->addSql('ALTER TABLE tendance_emotionnelle DROP FOREIGN KEY fk_tendance_utilisateur');
        $this->addSql('DROP INDEX uq_tendance_unique ON tendance_emotionnelle');
        $this->addSql('DROP INDEX IDX_4D7A6A6050EAE44 ON tendance_emotionnelle');
        $this->addSql('ALTER TABLE tendance_emotionnelle ADD totale_occurrences INT NOT NULL, ADD utilisateur_id INT NOT NULL, DROP id_utilisateur, DROP total_occurrences, CHANGE mois mois SMALLINT NOT NULL, CHANGE emotion emotion VARCHAR(20) NOT NULL, CHANGE date_calcul date_calcul DATETIME NOT NULL, CHANGE id_tendance id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE tendance_emotionnelle ADD CONSTRAINT FK_4D7A6A60FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_4D7A6A60FB88E14F ON tendance_emotionnelle (utilisateur_id)');
        $this->addSql('ALTER TABLE urgence CHANGE type_urgence type_urgence VARCHAR(50) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE severity_level severity_level SMALLINT DEFAULT NULL, CHANGE status status VARCHAR(30) DEFAULT NULL, CHANGE location location VARCHAR(100) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE id_urgence id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sessionactivite (id_session INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, id_activite INT NOT NULL, date_debut DATETIME DEFAULT \'current_timestamp()\' NOT NULL, date_fin DATETIME DEFAULT \'NULL\', duree_reelle INT DEFAULT NULL, statut_session ENUM(\'en_cours\', \'completee\', \'abandonnee\', \'planifiee\') CHARACTER SET utf8mb4 DEFAULT \'\'\'en_cours\'\'\' COLLATE `utf8mb4_general_ci`, humeur_avant ENUM(\'tres_bien\', \'bien\', \'neutre\', \'pas_bien\', \'tres_mal\') CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, score_humeur_avant TINYINT(1) DEFAULT NULL, emotion_avant VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, humeur_apres ENUM(\'tres_bien\', \'bien\', \'neutre\', \'pas_bien\', \'tres_mal\') CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, score_humeur_apres TINYINT(1) DEFAULT NULL, emotion_apres VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, note_satisfaction TINYINT(1) DEFAULT NULL, commentaire VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, impact_percu ENUM(\'tres_positif\', \'positif\', \'neutre\', \'negatif\') CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, est_objectif_atteint TINYINT(1) DEFAULT NULL, frequence_souhaitee ENUM(\'quotidienne\', \'hebdomadaire\', \'occasionnelle\') CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, recommande_par ENUM(\'ia\', \'professionnel\', \'auto\') CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, score_recommandation_ia NUMERIC(3, 2) DEFAULT \'NULL\', INDEX idx_session_activite (id_activite), INDEX idx_session_statut (statut_session), INDEX idx_session_utilisateur (id_utilisateur), INDEX idx_session_date (date_debut), PRIMARY KEY(id_session)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE utilisateurs (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, mot_de_passe VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, role ENUM(\'admin\', \'professionnel\', \'client\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, statut ENUM(\'actif\', \'suspendu\') CHARACTER SET utf8mb4 DEFAULT \'\'\'actif\'\'\' COLLATE `utf8mb4_general_ci`, date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sessionactivite ADD CONSTRAINT fk_session_activite FOREIGN KEY (id_activite) REFERENCES activite_bien_etre (id_activite) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sessionactivite ADD CONSTRAINT fk_session_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357DFB88E14F');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357D9B0F88B1');
        $this->addSql('DROP TABLE session_activite');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B3345E0A3');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05BFB88E14F');
        $this->addSql('DROP INDEX IDX_2130A05B3345E0A3 ON accompagnement');
        $this->addSql('DROP INDEX IDX_2130A05BFB88E14F ON accompagnement');
        $this->addSql('ALTER TABLE accompagnement ADD id_rendez_vous INT NOT NULL, ADD id_utilisateur INT NOT NULL, DROP rendezvous_id, DROP utilisateur_id, CHANGE prochain_rdv prochain_rdv ENUM(\'oui\', \'non\') DEFAULT \'\'\'non\'\'\' NOT NULL, CHANGE date_prochain_rdv date_prochain_rdv DATE DEFAULT \'NULL\', CHANGE objectifs objectifs TEXT DEFAULT NULL, CHANGE notes_suivi notes_suivi TEXT DEFAULT NULL, CHANGE niveau_priorite niveau_priorite TINYINT(1) DEFAULT 3, CHANGE id id_accompagnement INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_accompagnement)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT fk_accomp_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT fk_accomp_rdv FOREIGN KEY (id_rendez_vous) REFERENCES rendez_vous (id_rendez_vous) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_accomp_rdv ON accompagnement (id_rendez_vous)');
        $this->addSql('CREATE INDEX fk_accomp_user ON accompagnement (id_utilisateur)');
        $this->addSql('ALTER TABLE activite_bien_etre DROP FOREIGN KEY FK_362E6BCBFC29C013');
        $this->addSql('DROP INDEX IDX_362E6BCBFC29C013 ON activite_bien_etre');
        $this->addSql('ALTER TABLE activite_bien_etre CHANGE description description TEXT DEFAULT NULL, CHANGE categorie categorie VARCHAR(100) DEFAULT \'NULL\', CHANGE niveau_difficulte niveau_difficulte ENUM(\'facile\', \'modere\', \'difficile\') DEFAULT \'\'\'facile\'\'\', CHANGE objectif_emotionnel objectif_emotionnel VARCHAR(200) DEFAULT \'NULL\', CHANGE est_predefinie est_predefinie TINYINT(1) DEFAULT 1, CHANGE est_active est_active TINYINT(1) DEFAULT 1, CHANGE image_url image_url VARCHAR(500) DEFAULT \'NULL\', CHANGE instructions_detaillees instructions_detaillees TEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT \'current_timestamp()\', CHANGE id id_activite INT AUTO_INCREMENT NOT NULL, CHANGE cree_par_id cree_par INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_activite)');
        $this->addSql('ALTER TABLE activite_bien_etre ADD CONSTRAINT fk_activite_createur FOREIGN KEY (cree_par) REFERENCES utilisateurs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_activite_type ON activite_bien_etre (type_activite)');
        $this->addSql('CREATE INDEX idx_activite_categorie ON activite_bien_etre (categorie)');
        $this->addSql('CREATE INDEX fk_activite_createur ON activite_bien_etre (cree_par)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA76ED395');
        $this->addSql('DROP INDEX IDX_67F068BC38B217A7 ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BCA76ED395 ON commentaire');
        $this->addSql('ALTER TABLE commentaire ADD id_publication INT NOT NULL, ADD id_user INT NOT NULL, DROP publication_id, DROP user_id, CHANGE contenu contenu TEXT NOT NULL, CHANGE date_commentaire date_commentaire DATETIME DEFAULT \'current_timestamp()\', CHANGE id id_commentaire INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_commentaire)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT fk_commentaire_user FOREIGN KEY (id_user) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT fk_commentaire_publication FOREIGN KEY (id_publication) REFERENCES publication (id_publication) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_commentaire_publication ON commentaire (id_publication)');
        $this->addSql('CREATE INDEX fk_commentaire_user ON commentaire (id_user)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur DROP INDEX UNIQ_7AF1B92FB88E14F, ADD INDEX fk_conf_user (utilisateur_id)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur DROP FOREIGN KEY FK_7AF1B92FB88E14F');
        $this->addSql('ALTER TABLE confidentialite_utilisateur CHANGE partage_donnees partage_donnees TINYINT(1) DEFAULT 0, CHANGE notifications_email notifications_email TINYINT(1) DEFAULT 1, CHANGE visibilite_profil visibilite_profil TINYINT(1) DEFAULT 1, CHANGE date_modification date_modification DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE confidentialite_utilisateur ADD CONSTRAINT fk_conf_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB578B7FBD');
        $this->addSql('ALTER TABLE intervention CHANGE intervention_type intervention_type VARCHAR(50) DEFAULT \'NULL\', CHANGE notes notes VARCHAR(255) DEFAULT \'NULL\', CHANGE result result VARCHAR(50) DEFAULT \'NULL\', CHANGE intervention_date intervention_date DATETIME DEFAULT \'NULL\', CHANGE id id_intervention INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_intervention)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT fk_intervention_urgence FOREIGN KEY (urgence_id) REFERENCES urgence (id_urgence) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intervention RENAME INDEX idx_d11814ab578b7fbd TO fk_intervention_urgence');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FFB88E14F');
        $this->addSql('DROP INDEX IDX_443F70FFB88E14F ON journal_emotionnel');
        $this->addSql('ALTER TABLE journal_emotionnel DROP audio, CHANGE emotion emotion ENUM(\'tres_bien\', \'bien\', \'neutre\', \'pas_bien\', \'tres_mal\') NOT NULL, CHANGE contenu contenu TEXT NOT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE date_creation date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE id id_journal INT AUTO_INCREMENT NOT NULL, CHANGE utilisateur_id id_utilisateur INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_journal)');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT fk_journal_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_journal_utilisateur ON journal_emotionnel (id_utilisateur)');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779A76ED395');
        $this->addSql('DROP INDEX IDX_AF3C6779A76ED395 ON publication');
        $this->addSql('ALTER TABLE publication CHANGE contenu contenu TEXT NOT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE date_publication date_publication DATETIME DEFAULT \'current_timestamp()\', CHANGE id id_publication INT AUTO_INCREMENT NOT NULL, CHANGE user_id id_user INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_publication)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT fk_publication_user FOREIGN KEY (id_user) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_publication_user ON publication (id_user)');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AFB88E14F');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8A49CC82');
        $this->addSql('DROP INDEX IDX_65E8AA0AFB88E14F ON rendez_vous');
        $this->addSql('DROP INDEX IDX_65E8AA0A8A49CC82 ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous ADD id_utilisateur INT NOT NULL, ADD id_professionnel INT NOT NULL, DROP utilisateur_id, DROP professionnel_id, CHANGE mode mode ENUM(\'en_ligne\', \'en_presentiel\') NOT NULL, CHANGE localisation localisation VARCHAR(255) DEFAULT \'NULL\', CHANGE statut statut ENUM(\'planifie\', \'honore\', \'annule\', \'non_honore\') DEFAULT \'\'\'planifie\'\'\', CHANGE commentaire commentaire VARCHAR(255) DEFAULT \'NULL\', CHANGE date_creation date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE id id_rendez_vous INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_rendez_vous)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT fk_rdv_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT fk_rdv_professionnel FOREIGN KEY (id_professionnel) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_rdv_professionnel ON rendez_vous (id_professionnel)');
        $this->addSql('CREATE INDEX fk_rdv_utilisateur ON rendez_vous (id_utilisateur)');
        $this->addSql('ALTER TABLE tendance_emotionnelle DROP FOREIGN KEY FK_4D7A6A60FB88E14F');
        $this->addSql('DROP INDEX IDX_4D7A6A60FB88E14F ON tendance_emotionnelle');
        $this->addSql('ALTER TABLE tendance_emotionnelle ADD id_utilisateur INT NOT NULL, ADD total_occurrences INT NOT NULL, DROP totale_occurrences, DROP utilisateur_id, CHANGE mois mois TINYINT(1) NOT NULL, CHANGE emotion emotion ENUM(\'tres_bien\', \'bien\', \'neutre\', \'pas_bien\', \'tres_mal\') NOT NULL, CHANGE date_calcul date_calcul DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE id id_tendance INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_tendance)');
        $this->addSql('ALTER TABLE tendance_emotionnelle ADD CONSTRAINT fk_tendance_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX uq_tendance_unique ON tendance_emotionnelle (id_utilisateur, mois, annee, emotion)');
        $this->addSql('CREATE INDEX IDX_4D7A6A6050EAE44 ON tendance_emotionnelle (id_utilisateur)');
        $this->addSql('ALTER TABLE urgence CHANGE type_urgence type_urgence VARCHAR(50) DEFAULT \'NULL\', CHANGE description description VARCHAR(255) DEFAULT \'NULL\', CHANGE severity_level severity_level TINYINT(1) DEFAULT NULL, CHANGE status status VARCHAR(30) DEFAULT \'NULL\', CHANGE location location VARCHAR(100) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\', CHANGE id id_urgence INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_urgence)');
    }
}
