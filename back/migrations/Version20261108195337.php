<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108195337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Questionnaires : modèles et origine des questions (comparaison annuelle).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire ADD est_modele TINYINT DEFAULT 0 NOT NULL, ADD modele_origine_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAFF3D08E7F FOREIGN KEY (modele_origine_id) REFERENCES questionnaire (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7A64DAFF3D08E7F ON questionnaire (modele_origine_id)');
        $this->addSql('ALTER TABLE questionnaire_question ADD origine VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFF3D08E7F');
        $this->addSql('DROP INDEX IDX_7A64DAFF3D08E7F ON questionnaire');
        $this->addSql('ALTER TABLE questionnaire DROP est_modele, DROP modele_origine_id');
        $this->addSql('ALTER TABLE questionnaire_question DROP origine');
    }
}
