<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Finances : bons de livraison (PDF) par réception et visa du service financier sur chaque réception.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE finance_reception_document (id INT AUTO_INCREMENT NOT NULL, chemin VARCHAR(255) NOT NULL, nom_fichier VARCHAR(255) NOT NULL, date_depot DATETIME(6) NOT NULL, reception_id INT NOT NULL, depose_par_id INT DEFAULT NULL, INDEX IDX_E46B496F7C14DF52 (reception_id), INDEX IDX_E46B496FDCFF0FC4 (depose_par_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE finance_reception_document ADD CONSTRAINT FK_E46B496F7C14DF52 FOREIGN KEY (reception_id) REFERENCES finance_reception (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_reception_document ADD CONSTRAINT FK_E46B496FDCFF0FC4 FOREIGN KEY (depose_par_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE finance_reception ADD date_visa DATETIME(6) DEFAULT NULL, ADD viseur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE finance_reception ADD CONSTRAINT FK_3301BF0346A95AB4 FOREIGN KEY (viseur_id) REFERENCES personnel (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3301BF0346A95AB4 ON finance_reception (viseur_id)');
        // Réceptions des demandes déjà certifiées : visées par le certificateur, à la date de certification.
        $this->addSql('UPDATE finance_reception r INNER JOIN finance_bon_commande c ON c.id = r.bon_commande_id SET r.date_visa = c.date_certification_service_fait, r.viseur_id = c.certificateur_service_fait_id WHERE c.date_certification_service_fait IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE finance_reception DROP FOREIGN KEY FK_3301BF0346A95AB4');
        $this->addSql('DROP INDEX IDX_3301BF0346A95AB4 ON finance_reception');
        $this->addSql('ALTER TABLE finance_reception DROP date_visa, DROP viseur_id');
        $this->addSql('DROP TABLE finance_reception_document');
    }
}
