<?php

declare(strict_types=1);

namespace ParcautoBundle\Entity;

use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parcauto_reservation')]
#[ORM\Index(name: 'idx_parcauto_reservation_planning', columns: ['vehicule_id', 'statut', 'debut', 'fin'])]
class Reservation
{
    public const DEMANDEE = 'demandee';
    public const VALIDEE = 'validee';
    public const EN_COURS = 'en_cours';
    public const TERMINEE = 'terminee';
    public const REFUSEE = 'refusee';

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $motifRefus = null;

    public const ANNULEE = 'annulee';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(length: 16)]
    public private(set) string $statut = self::DEMANDEE;

    #[ORM\Column(nullable: true)]
    public private(set) ?\DateTimeImmutable $departLe = null;

    #[ORM\Column(nullable: true)]
    public private(set) ?\DateTimeImmutable $retourLe = null;

    #[ORM\Column(nullable: true)]
    public private(set) ?int $kilometrageDepart = null;

    #[ORM\Column(nullable: true)]
    public private(set) ?int $kilometrageRetour = null;

    #[ORM\Column(length: 200)]
    public readonly string $nomConducteur;

    public function __construct(
        #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
        public readonly Vehicule $vehicule,
        #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
        public readonly Personnel $conducteur,
        #[ORM\Column]
        public private(set) \DateTimeImmutable $debut,
        #[ORM\Column]
        public private(set) \DateTimeImmutable $fin,
        #[ORM\Column(length: 120, nullable: true)]
        public readonly ?string $ordreMission,
        #[ORM\Column(length: 10, nullable: true)]
        public readonly ?string $typeOm = null,
        #[ORM\Column(length: 255, nullable: true)]
        public readonly ?string $motif = null,
        #[ORM\Column(length: 500, nullable: true)]
        public readonly ?string $destinations = null,
        #[ORM\Column(options: ['default' => false])]
        public readonly bool $urgence = false,
    ) {
        $this->nomConducteur = trim($conducteur->getPrenom().' '.$conducteur->getNom());
    }

    #[ORM\Column]
    public private(set) bool $ordreVerifie = false;

    public function valider(bool $ordreVerifie = false): void
    {
        $this->exigerStatut(self::DEMANDEE);
        $this->ordreVerifie = $ordreVerifie;
        $this->statut = self::VALIDEE;
    }

    public function refuser(?string $motif): void
    {
        $this->exigerStatut(self::DEMANDEE);
        $this->motifRefus = $motif;
        $this->statut = self::REFUSEE;
    }

    public function annuler(): void
    {
        if (!in_array($this->statut, [self::DEMANDEE, self::VALIDEE], true)) {
            throw new \DomainException('Cette réservation ne peut plus être annulée.');
        }
        $this->statut = self::ANNULEE;
    }

    public function partir(int $kilometrage, \DateTimeImmutable $date): void
    {
        $this->exigerStatut($this->urgence ? self::DEMANDEE : self::VALIDEE);
        $this->vehicule->releverKilometrage($kilometrage);
        $this->kilometrageDepart = $kilometrage;
        $this->departLe = $date;
        $this->statut = self::EN_COURS;
    }

    public function retourner(int $kilometrage, \DateTimeImmutable $date): void
    {
        $this->exigerStatut(self::EN_COURS);
        $this->vehicule->releverKilometrage($kilometrage);
        $this->kilometrageRetour = $kilometrage;
        $this->retourLe = $date;
        $this->statut = self::TERMINEE;
    }

    private function exigerStatut(string $statut): void
    {
        if ($this->statut !== $statut) {
            throw new \DomainException('Cette action est incompatible avec le statut de la réservation.');
        }
    }
}
