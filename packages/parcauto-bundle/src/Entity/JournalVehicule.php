<?php

declare(strict_types=1);

namespace ParcautoBundle\Entity;

use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parcauto_journal_vehicule')]
#[ORM\Index(name: 'idx_parcauto_journal', columns: ['vehicule_id', 'type', 'date'])]
class JournalVehicule
{
    public const ENTRETIEN = 'entretien';
    public const CARBURANT = 'carburant';
    public const FACTURE = 'facture';
    public const PNEUS = 'pneus';
    public const REMPLACEMENT_PNEUS = 'remplacement_pneus';
    public const INCIDENT = 'incident';
    public const REMISE_SERVICE = 'remise_service';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public private(set) ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
        public readonly Vehicule $vehicule,
        #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
        public readonly Personnel $auteur,
        #[ORM\Column(length: 25)]
        public readonly string $type,
        #[ORM\Column]
        public readonly \DateTimeImmutable $date,
        #[ORM\Column(length: 255)]
        public readonly string $libelle,
        #[ORM\Column(nullable: true)]
        public readonly ?int $kilometrage = null,
        #[ORM\Column(nullable: true)]
        public readonly ?int $montantCentimes = null,
        #[ORM\Column(nullable: true)]
        public readonly ?int $volumeMillilitres = null,
        #[ORM\ManyToOne]
        public readonly ?Equipement $prestataire = null,
        #[ORM\ManyToOne]
        public readonly ?Equipement $carte = null,
        #[ORM\Column(nullable: true)]
        public readonly ?int $usureDixiemesMm = null,
    ) {
    }
}
