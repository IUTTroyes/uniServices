<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Repository\GrilleRepository;

/**
 * Grille d'évaluation, rattachée à un type de diplôme APC (D1) et éventuellement
 * restreinte à un département ou à un diplôme. Son contenu (échelle, critères)
 * vit dans des versions : une version publiée est figée.
 */
#[ORM\Entity(repositoryClass: GrilleRepository::class)]
#[ORM\Table(name: 'unifolio_grille')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_grille_uuid', columns: ['uuid'])]
class Grille implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    public const PORTEE_TYPE_DIPLOME = 'type_diplome';
    public const PORTEE_DEPARTEMENT = 'departement';
    public const PORTEE_DIPLOME = 'diplome';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureTypeDiplome $typeDiplome;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?StructureDepartement $departement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?StructureDiplome $diplome = null;

    #[ORM\Column(length: 20, enumType: GranulariteEnum::class)]
    private GranulariteEnum $granularite;

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    /** @var Collection<int, GrilleVersion> */
    #[ORM\OneToMany(targetEntity: GrilleVersion::class, mappedBy: 'grille', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['numero' => 'ASC'])]
    private Collection $versions;

    public function __construct(
        string $libelle,
        StructureTypeDiplome $typeDiplome,
        GranulariteEnum $granularite,
        ?StructureDepartement $departement = null,
        ?StructureDiplome $diplome = null,
    ) {
        if ($typeDiplome->isApc() !== true) {
            throw new \InvalidArgumentException('Les grilles UniFolio sont réservées aux diplômes APC (BUT).');
        }
        if ($diplome !== null) {
            if ($diplome->getTypeDiplome()?->getId() !== $typeDiplome->getId()) {
                throw new \InvalidArgumentException('Le diplôme ne correspond pas au type de diplôme de la grille.');
            }
            $departement = $diplome->getDepartement();
        }
        $this->setLibelle($libelle);
        $this->typeDiplome = $typeDiplome;
        $this->granularite = $granularite;
        $this->departement = $departement;
        $this->diplome = $diplome;
        $this->versions = new ArrayCollection();
        $this->setUuid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $libelle = trim($libelle);
        if ($libelle === '') {
            throw new \InvalidArgumentException('Le libellé de la grille est obligatoire.');
        }
        $this->libelle = mb_substr($libelle, 0, 255);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description !== null && trim($description) !== '' ? $description : null;

        return $this;
    }

    public function getTypeDiplome(): StructureTypeDiplome
    {
        return $this->typeDiplome;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function getDiplome(): ?StructureDiplome
    {
        return $this->diplome;
    }

    public function getGranularite(): GranulariteEnum
    {
        return $this->granularite;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getPortee(): string
    {
        return match (true) {
            $this->diplome !== null => self::PORTEE_DIPLOME,
            $this->departement !== null => self::PORTEE_DEPARTEMENT,
            default => self::PORTEE_TYPE_DIPLOME,
        };
    }

    /** Plus la grille est ciblée, plus elle est prioritaire : diplôme > département > type de diplôme. */
    public function getSpecificite(): int
    {
        return match ($this->getPortee()) {
            self::PORTEE_DIPLOME => 2,
            self::PORTEE_DEPARTEMENT => 1,
            default => 0,
        };
    }

    public function estApplicableA(StructureDiplome $diplome): bool
    {
        if ($diplome->getTypeDiplome()?->getId() !== $this->typeDiplome->getId()) {
            return false;
        }
        if ($this->diplome !== null && $this->diplome->getId() !== $diplome->getId()) {
            return false;
        }
        if ($this->departement !== null && $this->departement->getId() !== $diplome->getDepartement()?->getId()) {
            return false;
        }

        return true;
    }

    /** @return Collection<int, GrilleVersion> */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    /** Dernière version publiée : celle utilisée pour les nouvelles évaluations. */
    public function getVersionPubliee(): ?GrilleVersion
    {
        $publiee = null;
        foreach ($this->versions as $version) {
            if ($version->isPubliee() && ($publiee === null || $version->getNumero() > $publiee->getNumero())) {
                $publiee = $version;
            }
        }

        return $publiee;
    }

    public function getBrouillon(): ?GrilleVersion
    {
        foreach ($this->versions as $version) {
            if (!$version->isPubliee()) {
                return $version;
            }
        }

        return null;
    }

    public function hasVersionPubliee(): bool
    {
        return $this->getVersionPubliee() !== null;
    }

    /**
     * Brouillon de la prochaine version, recopié de la dernière version publiée
     * (ou vide avec l'échelle par défaut pour une grille neuve).
     */
    public function creerBrouillon(): GrilleVersion
    {
        if (($brouillon = $this->getBrouillon()) !== null) {
            return $brouillon;
        }
        $numero = 1;
        foreach ($this->versions as $version) {
            $numero = max($numero, $version->getNumero() + 1);
        }
        $brouillon = new GrilleVersion($this, $numero);
        $source = $this->getVersionPubliee();
        if ($source !== null) {
            $source->copierContenuVers($brouillon);
        } else {
            $brouillon->appliquerEchelleParDefaut();
        }
        $this->versions->add($brouillon);

        return $brouillon;
    }

    public function supprimerBrouillon(): void
    {
        if (($brouillon = $this->getBrouillon()) !== null) {
            $this->versions->removeElement($brouillon);
        }
    }
}
