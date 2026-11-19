<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enregistre la révision source et le motif des restaurations de sous-commission.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intranet_sous_commission_revision ADD restauree_depuis_id INT DEFAULT NULL, ADD motif_restauration LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_COMMISSION_REVISION_SOURCE ON intranet_sous_commission_revision (restauree_depuis_id)');
        $this->addSql('ALTER TABLE intranet_sous_commission_revision ADD CONSTRAINT FK_COMMISSION_REVISION_SOURCE FOREIGN KEY (restauree_depuis_id) REFERENCES intranet_sous_commission_revision (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intranet_sous_commission_revision DROP FOREIGN KEY FK_COMMISSION_REVISION_SOURCE');
        $this->addSql('DROP INDEX IDX_COMMISSION_REVISION_SOURCE ON intranet_sous_commission_revision');
        $this->addSql('ALTER TABLE intranet_sous_commission_revision DROP restauree_depuis_id, DROP motif_restauration');
    }
}
