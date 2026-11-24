<?php

declare(strict_types=1);

namespace ParcautoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parcauto_equipement')]
#[ORM\UniqueConstraint(name: 'uniq_parcauto_equipement', columns: ['type', 'reference'])]
class Equipement
{
    public const PRESTATAIRE = 'prestataire';
    public const CARTE = 'carte';
    public const PASS = 'pass_telepeage';
    public const BORNE = 'borne';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public private(set) ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 20)]
        public readonly string $type,
        #[ORM\Column(length: 120)]
        public readonly string $reference,
        #[ORM\Column(length: 150)]
        public string $libelle,
        #[ORM\Column(length: 255)]
        public string $coordonnees,
        #[ORM\ManyToOne]
        public ?Vehicule $vehicule,
        #[ORM\Column]
        public bool $actif,
    ) {
    }
}
