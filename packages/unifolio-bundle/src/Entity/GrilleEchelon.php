<?php

namespace UnifolioBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Niveau de l'échelle commune d'une version de grille (ex. « Acquis », valeur 2). */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_grille_echelon')]
class GrilleEchelon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'echelons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GrilleVersion $version;

    #[ORM\Column(length: 100)]
    private string $libelle;

    #[ORM\Column(type: Types::FLOAT)]
    private float $valeur;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripteur = null;

    #[ORM\Column]
    private int $ordre = 0;

    public function __construct(GrilleVersion $version, string $libelle, float $valeur, ?string $couleur = null, ?string $descripteur = null)
    {
        $libelle = trim($libelle);
        if ($libelle === '') {
            throw new \InvalidArgumentException('Chaque niveau de l\'échelle doit avoir un libellé.');
        }
        if ($couleur !== null && $couleur !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $couleur)) {
            throw new \InvalidArgumentException('Couleur invalide (format attendu : #RRGGBB).');
        }
        $this->version = $version;
        $this->libelle = mb_substr($libelle, 0, 100);
        $this->valeur = $valeur;
        $this->couleur = $couleur !== '' ? $couleur : null;
        $this->descripteur = $descripteur !== null && trim($descripteur) !== '' ? $descripteur : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): GrilleVersion
    {
        return $this->version;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function getValeur(): float
    {
        return $this->valeur;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function getDescripteur(): ?string
    {
        return $this->descripteur;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }
}
