<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\TypePortfolioEnum;
use UnifolioBundle\Repository\PortfolioModeleRepository;

/**
 * Modèle de portfolio défini par un référent : pages et blocs pré-remplis, dont certains verrouillés
 * (non supprimables par l'étudiant). Le portfolio créé à partir d'un modèle en reçoit une COPIE :
 * modifier le modèle ensuite ne change pas les portfolios existants.
 */
#[ORM\Entity(repositoryClass: PortfolioModeleRepository::class)]
#[ORM\Table(name: 'unifolio_portfolio_modele')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_modele_uuid', columns: ['uuid'])]
class PortfolioModele implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** null = modèle national (géré par un super administrateur). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?StructureDepartement $departement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?StructureDiplome $diplome = null;

    #[ORM\Column(length: 20, enumType: TypePortfolioEnum::class)]
    private TypePortfolioEnum $type = TypePortfolioEnum::ACADEMIQUE;

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    /**
     * Pages du modèle : [{ titre, verrouillee, blocs: [...], verrouilles: [ids de blocs] }].
     *
     * @var list<array{titre: string, verrouillee: bool, blocs: list<array<string, mixed>>, verrouilles: list<string>}>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $pages = [];

    public function __construct(string $libelle, ?StructureDepartement $departement = null, ?StructureDiplome $diplome = null)
    {
        $this->setLibelle($libelle);
        if ($diplome !== null) {
            $departement = $diplome->getDepartement();
        }
        $this->departement = $departement;
        $this->diplome = $diplome;
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
            throw new \InvalidArgumentException('Le libellé du modèle est obligatoire.');
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

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function getDiplome(): ?StructureDiplome
    {
        return $this->diplome;
    }

    public function getType(): TypePortfolioEnum
    {
        return $this->type;
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

    /** @return list<array{titre: string, verrouillee: bool, blocs: list<array<string, mixed>>, verrouilles: list<string>}> */
    public function getPages(): array
    {
        return $this->pages;
    }

    /** @param list<array{titre: string, verrouillee: bool, blocs: list<array<string, mixed>>, verrouilles: list<string>}> $pages */
    public function setPages(array $pages): static
    {
        $this->pages = $pages;

        return $this;
    }

    public function estApplicableA(StructureDiplome $diplome): bool
    {
        if ($this->diplome !== null && $this->diplome->getId() !== $diplome->getId()) {
            return false;
        }

        return $this->departement === null || $this->departement->getId() === $diplome->getDepartement()?->getId();
    }
}
