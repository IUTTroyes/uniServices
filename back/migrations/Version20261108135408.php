<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108135408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Questionnaires : suppression en cascade des réponses (invitation, section publiée, question).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire_answer DROP FOREIGN KEY `FK_437B451C1E27F6BF`');
        $this->addSql('ALTER TABLE questionnaire_answer DROP FOREIGN KEY `FK_437B451CA35D7AF0`');
        $this->addSql('ALTER TABLE questionnaire_answer DROP FOREIGN KEY `FK_437B451CD823E37A`');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT FK_437B451C1E27F6BF FOREIGN KEY (question_id) REFERENCES questionnaire_question (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT FK_437B451CA35D7AF0 FOREIGN KEY (invitation_id) REFERENCES questionnaire_invitation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT FK_437B451CD823E37A FOREIGN KEY (section_id) REFERENCES questionnaire_section_instance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questionnaire_section_instance DROP FOREIGN KEY `FK_8F705045D823E37A`');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT FK_8F705045D823E37A FOREIGN KEY (section_id) REFERENCES questionnaire_section (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE questionnaire_answer DROP FOREIGN KEY FK_437B451CA35D7AF0');
        $this->addSql('ALTER TABLE questionnaire_answer DROP FOREIGN KEY FK_437B451CD823E37A');
        $this->addSql('ALTER TABLE questionnaire_answer DROP FOREIGN KEY FK_437B451C1E27F6BF');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT `FK_437B451CA35D7AF0` FOREIGN KEY (invitation_id) REFERENCES questionnaire_invitation (id)');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT `FK_437B451CD823E37A` FOREIGN KEY (section_id) REFERENCES questionnaire_section_instance (id)');
        $this->addSql('ALTER TABLE questionnaire_answer ADD CONSTRAINT `FK_437B451C1E27F6BF` FOREIGN KEY (question_id) REFERENCES questionnaire_question (id)');
        $this->addSql('ALTER TABLE questionnaire_section_instance DROP FOREIGN KEY FK_8F705045D823E37A');
        $this->addSql('ALTER TABLE questionnaire_section_instance ADD CONSTRAINT `FK_8F705045D823E37A` FOREIGN KEY (section_id) REFERENCES questionnaire_section (id)');
    }
}
