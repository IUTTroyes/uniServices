<?php

declare(strict_types=1);

namespace ParcautoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parcauto_valeur_referentiel')]
class ValeurReferentiel
{
    public const GROUPES = [
        'energie' => [Vehicule::class, 'energie'],
        'equipement' => [Equipement::class, 'type'],
        'plan' => [EcheanceEntretien::class, 'type'],
        'journal' => [JournalVehicule::class, 'type'],
    ];

    public function __construct(
        #[ORM\Id, ORM\Column(length: 20)]
        public readonly string $groupe,
        #[ORM\Id, ORM\Column(length: 30)]
        public readonly string $code,
        #[ORM\Column(length: 120)]
        public string $libelle,
        #[ORM\Column]
        public readonly bool $protegee = false,
    ) {
    }
}
