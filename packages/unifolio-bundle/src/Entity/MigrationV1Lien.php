<?php

namespace UnifolioBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Correspondance entre un objet d'UniFolio v1 et son équivalent v2. Rend la migration rejouable (un objet déjà migré
 * n'est pas recréé) et réversible (purge de ce que la migration a créé).
 */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_migration_v1')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_migration_v1', columns: ['type', 'v1_id'])]
class MigrationV1Lien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $type;

    #[ORM\Column]
    private int $v1Id;

    /** Identifiant de l'objet v2 correspondant. */
    #[ORM\Column]
    private int $cibleId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creeLe;

    public function __construct(string $type, int $v1Id, int $cibleId)
    {
        $this->type = $type;
        $this->v1Id = $v1Id;
        $this->cibleId = $cibleId;
        $this->creeLe = new \DateTimeImmutable();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getV1Id(): int
    {
        return $this->v1Id;
    }

    public function getCibleId(): int
    {
        return $this->cibleId;
    }
}
