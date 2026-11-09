<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108182430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Questionnaires : destinataires de la synthèse partagée.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE questionnaire_destinataire_synthese (questionnaire_id INT NOT NULL, personnel_id INT NOT NULL, INDEX IDX_36605112CE07E8FF (questionnaire_id), INDEX IDX_366051121C109075 (personnel_id), PRIMARY KEY (questionnaire_id, personnel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese ADD CONSTRAINT FK_36605112CE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese ADD CONSTRAINT FK_366051121C109075 FOREIGN KEY (personnel_id) REFERENCES personnel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese DROP FOREIGN KEY FK_36605112CE07E8FF');
        $this->addSql('ALTER TABLE questionnaire_destinataire_synthese DROP FOREIGN KEY FK_366051121C109075');
        $this->addSql('DROP TABLE questionnaire_destinataire_synthese');
    }
}
