<?php

declare(strict_types=1);

namespace ParcautoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parcauto_vehicule')]
class Vehicule
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $misEnServiceLe = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $reformePrevueLe = null;

    #[ORM\Column(nullable: true)]
    public ?int $reformeKm = null;

    #[ORM\Column]
    public int $achatCentimes = 0;

    #[ORM\Column(nullable: true)]
    public ?int $consommationReference = null;

    public function __construct(
        #[ORM\Column(length: 24, unique: true)]
        public private(set) string $immatriculation,
        #[ORM\Column(length: 150)]
        public private(set) string $libelle,
        #[ORM\Column(length: 120)]
        public private(set) string $site,
        #[ORM\Column(length: 30)]
        public private(set) string $energie,
        #[ORM\Column]
        public private(set) int $kilometrage,
        #[ORM\Column]
        public private(set) bool $actif = true,
    ) {
    }

    public function modifier(string $libelle, string $site, string $energie, bool $actif): void
    {
        $this->libelle = $libelle;
        $this->site = $site;
        $this->energie = $energie;
        $this->actif = $actif;
    }

    public function releverKilometrage(int $kilometrage): void
    {
        if ($kilometrage < $this->kilometrage) {
            throw new \DomainException('Le kilométrage ne peut pas diminuer.');
        }

        $this->kilometrage = $kilometrage;
    }
}
