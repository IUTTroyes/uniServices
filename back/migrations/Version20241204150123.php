<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241204150123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE etudiant ADD key_edu_sign VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE etudiant_absence ADD personnel_id INT DEFAULT NULL, ADD etudiant_id INT DEFAULT NULL, ADD enseignement_id INT DEFAULT NULL, ADD scolarite_id INT NOT NULL, ADD date_heure DATETIME(6) NOT NULL, ADD duree DATETIME(6) NOT NULL, ADD justifiee TINYINT(1) NOT NULL, ADD date_justification DATETIME(6) DEFAULT NULL, ADD key_edu_sign VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4D1C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id)');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4DDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4DABEC3B20 FOREIGN KEY (enseignement_id) REFERENCES scol_enseignement (id)');
        $this->addSql('ALTER TABLE etudiant_absence ADD CONSTRAINT FK_A2F5AE4DAA6B2AB6 FOREIGN KEY (scolarite_id) REFERENCES etudiant_scolarite (id)');
        $this->addSql('CREATE INDEX IDX_A2F5AE4D1C109075 ON etudiant_absence (personnel_id)');
        $this->addSql('CREATE INDEX IDX_A2F5AE4DDDEAB1A3 ON etudiant_absence (etudiant_id)');
        $this->addSql('CREATE INDEX IDX_A2F5AE4DABEC3B20 ON etudiant_absence (enseignement_id)');
        $this->addSql('CREATE INDEX IDX_A2F5AE4DAA6B2AB6 ON etudiant_absence (scolarite_id)');
        $this->addSql('ALTER TABLE personnel ADD id_edu_sign JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE structure_groupe ADD key_edu_sign VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE etudiant DROP key_edu_sign');
        $this->addSql('ALTER TABLE etudiant_absence DROP FOREIGN KEY FK_A2F5AE4D1C109075');
        $this->addSql('ALTER TABLE etudiant_absence DROP FOREIGN KEY FK_A2F5AE4DDDEAB1A3');
        $this->addSql('ALTER TABLE etudiant_absence DROP FOREIGN KEY FK_A2F5AE4DABEC3B20');
        $this->addSql('ALTER TABLE etudiant_absence DROP FOREIGN KEY FK_A2F5AE4DAA6B2AB6');
        $this->addSql('DROP INDEX IDX_A2F5AE4D1C109075 ON etudiant_absence');
        $this->addSql('DROP INDEX IDX_A2F5AE4DDDEAB1A3 ON etudiant_absence');
        $this->addSql('DROP INDEX IDX_A2F5AE4DABEC3B20 ON etudiant_absence');
        $this->addSql('DROP INDEX IDX_A2F5AE4DAA6B2AB6 ON etudiant_absence');
        $this->addSql('ALTER TABLE etudiant_absence DROP personnel_id, DROP etudiant_id, DROP enseignement_id, DROP scolarite_id, DROP date_heure, DROP duree, DROP justifiee, DROP date_justification, DROP key_edu_sign');
        $this->addSql('ALTER TABLE personnel DROP id_edu_sign');
        $this->addSql('ALTER TABLE structure_groupe DROP key_edu_sign');
    }
}
