<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213135117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accompagnement (id INT AUTO_INCREMENT NOT NULL, prochain_rdv VARCHAR(5) NOT NULL, date_prochain_rdv DATE DEFAULT NULL, objectifs LONGTEXT DEFAULT NULL, notes_suivi LONGTEXT DEFAULT NULL, niveau_priorite SMALLINT DEFAULT NULL, rendezvous_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_2130A05B3345E0A3 (rendezvous_id), INDEX IDX_2130A05BFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_commentaire DATETIME DEFAULT NULL, publication_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_67F068BC38B217A7 (publication_id), INDEX IDX_67F068BCA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE confidentialite_utilisateur (id INT AUTO_INCREMENT NOT NULL, partage_donnees TINYINT DEFAULT NULL, notifications_email TINYINT DEFAULT NULL, visibilite_profil TINYINT DEFAULT NULL, date_modification DATETIME NOT NULL, utilisateur_id INT NOT NULL, UNIQUE INDEX UNIQ_7AF1B92FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, intervention_type VARCHAR(50) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, result VARCHAR(50) DEFAULT NULL, intervention_date DATETIME DEFAULT NULL, urgence_id INT NOT NULL, INDEX IDX_D11814AB578B7FBD (urgence_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal_emotionnel (id INT AUTO_INCREMENT NOT NULL, emotion VARCHAR(20) NOT NULL, contenu LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, audio VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_443F70FFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publication (id INT AUTO_INCREMENT NOT NULL, likes_count INT NOT NULL, dislikes_count INT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, date_publication DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_AF3C6779A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, date_rdv DATE NOT NULL, heure_rdv TIME NOT NULL, mode VARCHAR(20) NOT NULL, localisation VARCHAR(255) DEFAULT NULL, statut VARCHAR(20) NOT NULL, commentaire VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, utilisateur_id INT NOT NULL, professionnel_id INT NOT NULL, INDEX IDX_65E8AA0AFB88E14F (utilisateur_id), INDEX IDX_65E8AA0A8A49CC82 (professionnel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tendance_emotionnelle (id INT AUTO_INCREMENT NOT NULL, mois SMALLINT NOT NULL, annee INT NOT NULL, emotion VARCHAR(20) NOT NULL, totale_occurrences INT NOT NULL, pourcentage NUMERIC(5, 2) NOT NULL, date_calcul DATETIME NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_4D7A6A60FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE urgence (id INT AUTO_INCREMENT NOT NULL, type_urgence VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, severity_level SMALLINT DEFAULT NULL, status VARCHAR(30) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(30) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05B3345E0A3 FOREIGN KEY (rendezvous_id) REFERENCES rendez_vous (id)');
        $this->addSql('ALTER TABLE accompagnement ADD CONSTRAINT FK_2130A05BFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE confidentialite_utilisateur ADD CONSTRAINT FK_7AF1B92FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB578B7FBD FOREIGN KEY (urgence_id) REFERENCES urgence (id)');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8A49CC82 FOREIGN KEY (professionnel_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE tendance_emotionnelle ADD CONSTRAINT FK_4D7A6A60FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05B3345E0A3');
        $this->addSql('ALTER TABLE accompagnement DROP FOREIGN KEY FK_2130A05BFB88E14F');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA76ED395');
        $this->addSql('ALTER TABLE confidentialite_utilisateur DROP FOREIGN KEY FK_7AF1B92FB88E14F');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB578B7FBD');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FFB88E14F');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779A76ED395');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AFB88E14F');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8A49CC82');
        $this->addSql('ALTER TABLE tendance_emotionnelle DROP FOREIGN KEY FK_4D7A6A60FB88E14F');
        $this->addSql('DROP TABLE accompagnement');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE confidentialite_utilisateur');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE journal_emotionnel');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE tendance_emotionnelle');
        $this->addSql('DROP TABLE urgence');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
