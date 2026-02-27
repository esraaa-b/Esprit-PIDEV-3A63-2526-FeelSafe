<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221001014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE translation_cache (id INT AUTO_INCREMENT NOT NULL, source_text_hash VARCHAR(64) NOT NULL, target_lang VARCHAR(10) NOT NULL, translated_text LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX source_target_idx (source_text_hash, target_lang), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
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
        $this->addSql('ALTER TABLE password_reset_token CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8A49CC82 FOREIGN KEY (professionnel_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE session_activite CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE session_activite ADD CONSTRAINT FK_33D9357D9B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite_bien_etre (id)');
        $this->addSql('ALTER TABLE tendance_emotionnelle CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE tendance_emotionnelle ADD CONSTRAINT FK_4D7A6A60FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE urgence CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id BIGINT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE translation_cache');
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
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE password_reset_token CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779A76ED395');
        $this->addSql('ALTER TABLE publication CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AFB88E14F');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8A49CC82');
        $this->addSql('ALTER TABLE rendez_vous CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357DFB88E14F');
        $this->addSql('ALTER TABLE session_activite DROP FOREIGN KEY FK_33D9357D9B0F88B1');
        $this->addSql('ALTER TABLE session_activite CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE tendance_emotionnelle DROP FOREIGN KEY FK_4D7A6A60FB88E14F');
        $this->addSql('ALTER TABLE tendance_emotionnelle CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE urgence CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE id id INT NOT NULL');
    }
}
