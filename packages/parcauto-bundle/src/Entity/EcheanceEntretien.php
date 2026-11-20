<?php

declare(strict_types=1);

namespace ParcautoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parcauto_echeance_entretien')]
#[ORM\UniqueConstraint(name: 'uniq_parcauto_vehicule_entretien', columns: ['vehicule_id', 'type', 'libelle'])]
class EcheanceEntretien
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(nullable: true)]
    public private(set) ?\DateTimeImmutable $effectueLe = null;

    #[ORM\Column(nullable: true)]
    public private(set) ?int $kilometrageControle = null;

    #[ORM\Column(nullable: true)]
    public ?int $intervalleMois = null;

    #[ORM\Column(nullable: true)]
    public ?int $intervalleKm = null;

    public function __construct(
        #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
        public readonly Vehicule $vehicule,
        #[ORM\Column(length: 30)]
        public readonly string $type,
        #[ORM\Column(length: 150)]
        public readonly string $libelle,
        #[ORM\Column]
        public private(set) \DateTimeImmutable $echeance,
        #[ORM\Column(nullable: true)]
        public private(set) ?int $echeanceKm = null,
    ) {
    }

    public function estDepassee(\DateTimeImmutable $date, int $kilometrage): bool
    {
        return $date >= $this->echeance || ($this->echeanceKm !== null && $kilometrage >= $this->echeanceKm);
    }

    public function renouveler(\DateTimeImmutable $date, \DateTimeImmutable $echeance, ?int $echeanceKm): void
    {
        $this->effectueLe = $date;
        $this->kilometrageControle = $this->vehicule->kilometrage;
        $this->echeance = $echeance;
        $this->echeanceKm = $echeanceKm;
    }
}
