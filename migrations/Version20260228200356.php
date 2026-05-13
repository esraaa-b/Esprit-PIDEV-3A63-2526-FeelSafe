<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228200356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE disponibilite (id INT AUTO_INCREMENT NOT NULL, professionnel_id INT NOT NULL, date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', heure_debut TIME NOT NULL COMMENT \'(DC2Type:time_immutable)\', heure_fin TIME NOT NULL COMMENT \'(DC2Type:time_immutable)\', INDEX IDX_2CBACE2F8A49CC82 (professionnel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE disponibilite ADD CONSTRAINT FK_2CBACE2F8A49CC82 FOREIGN KEY (professionnel_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE activite_bien_etre CHANGE date_creation date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire CHANGE date_commentaire date_commentaire DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE reported_at reported_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1BA9CD190');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intervention CHANGE intervention_date intervention_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE journal_emotionnel CHANGE date_creation date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE password_reset_token CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE publication CHANGE notification_date notification_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE pinned_at pinned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_publication date_publication DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE reported_at reported_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE rendez_vous CHANGE date_rdv date_rdv DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE heure_rdv heure_rdv TIME NOT NULL COMMENT \'(DC2Type:time_immutable)\', CHANGE date_creation date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE session_activite CHANGE date_debut date_debut DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_fin date_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE tendance_emotionnelle CHANGE date_calcul date_calcul DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE urgence CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE disponibilite DROP FOREIGN KEY FK_2CBACE2F8A49CC82');
        $this->addSql('DROP TABLE disponibilite');
        $this->addSql('ALTER TABLE activite_bien_etre CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire CHANGE date_commentaire date_commentaire DATETIME DEFAULT NULL, CHANGE reported_at reported_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id)');
        $this->addSql('ALTER TABLE commentaire_like DROP FOREIGN KEY FK_459B84E1BA9CD190');
        $this->addSql('ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id)');
        $this->addSql('ALTER TABLE intervention CHANGE intervention_date intervention_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE journal_emotionnel CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE password_reset_token CHANGE created_at created_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE publication CHANGE notification_date notification_date DATETIME DEFAULT NULL, CHANGE pinned_at pinned_at DATETIME DEFAULT NULL, CHANGE date_publication date_publication DATETIME DEFAULT NULL, CHANGE reported_at reported_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rendez_vous CHANGE date_rdv date_rdv DATE NOT NULL, CHANGE heure_rdv heure_rdv TIME NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE session_activite CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE tendance_emotionnelle CHANGE date_calcul date_calcul DATETIME NOT NULL');
        $this->addSql('ALTER TABLE urgence CHANGE created_at created_at DATETIME DEFAULT NULL');
    }
}
