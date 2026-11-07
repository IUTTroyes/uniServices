<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Une page d'un portfolio : un document de blocs (JSON) validé par BlocValidator. */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_portfolio_page')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_page_uuid', columns: ['uuid'])]
class PortfolioPage implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Portfolio $portfolio;

    #[ORM\Column(length: 255)]
    private string $titre;

    #[ORM\Column]
    private int $ordre;

    /** Imposée par le modèle : ne peut être ni supprimée ni renommée par l'étudiant. */
    #[ORM\Column(options: ['default' => false])]
    private bool $verrouillee = false;

    /**
     * @var list<array<string, mixed>>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $blocs = [];

    /**
     * Identifiants des blocs imposés par le modèle : ils ne peuvent être ni supprimés ni changés de type
     * (leur contenu reste modifiable par l'étudiant).
     *
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $verrouilles = [];

    /** Incrémenté à chaque enregistrement : détecte une modification concurrente (autre onglet). */
    #[ORM\Column(options: ['default' => 1])]
    private int $version = 1;

    public function __construct(Portfolio $portfolio, string $titre, int $ordre, bool $verrouillee = false)
    {
        $this->portfolio = $portfolio;
        $this->setTitre($titre);
        $this->ordre = $ordre;
        $this->verrouillee = $verrouillee;
        $this->setUuid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPortfolio(): Portfolio
    {
        return $this->portfolio;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $titre = trim($titre);
        if ($titre === '') {
            throw new \InvalidArgumentException('Le titre de la page est obligatoire.');
        }
        $this->titre = mb_substr($titre, 0, 255);

        return $this;
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

    public function isVerrouillee(): bool
    {
        return $this->verrouillee;
    }

    /** @return list<array<string, mixed>> */
    public function getBlocs(): array
    {
        return $this->blocs;
    }

    /** @return list<string> */
    public function getVerrouilles(): array
    {
        return $this->verrouilles;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Initialise le contenu depuis un modèle (identifiants régénérés pour que deux portfolios
     * issus du même modèle n'aient pas les mêmes blocs).
     *
     * @param list<array<string, mixed>> $blocs
     * @param list<string>               $verrouilles identifiants de blocs du modèle à verrouiller
     */
    public function initialiser(array $blocs, array $verrouilles): void
    {
        $correspondance = [];
        $this->blocs = $this->regenererIds($blocs, $correspondance);
        $this->verrouilles = array_values(array_filter(array_map(static fn (string $id) => $correspondance[$id] ?? null, $verrouilles)));
    }

    /**
     * Remplace les blocs (déjà validés). Les blocs imposés doivent subsister avec le même type.
     * Refuse l'enregistrement si la page a changé depuis la version connue du client.
     *
     * @param list<array<string, mixed>> $blocs
     */
    public function remplacerBlocs(array $blocs, int $versionConnue): void
    {
        if ($versionConnue !== $this->version) {
            throw new \DomainException('Cette page a été modifiée ailleurs (autre onglet ou appareil). Rechargez-la avant de continuer.');
        }
        $types = [];
        $this->collecterTypes($blocs, $types);
        foreach ($this->verrouilles as $id) {
            $ancien = $this->typeDe($this->blocs, $id);
            if (!isset($types[$id]) || ($ancien !== null && $types[$id] !== $ancien)) {
                throw new \DomainException('Un bloc imposé par le modèle ne peut pas être supprimé ni changé de type.');
            }
        }
        $this->blocs = $blocs;
        ++$this->version;
    }

    /**
     * @param list<array<string, mixed>> $blocs
     * @param array<string, string>      $correspondance ancien identifiant → nouveau
     *
     * @return list<array<string, mixed>>
     */
    private function regenererIds(array $blocs, array &$correspondance): array
    {
        foreach ($blocs as &$bloc) {
            $nouveau = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
            if (isset($bloc['id'])) {
                $correspondance[(string) $bloc['id']] = $nouveau;
            }
            $bloc['id'] = $nouveau;
            if (!empty($bloc['children'])) {
                $bloc['children'] = $this->regenererIds($bloc['children'], $correspondance);
            }
        }

        return $blocs;
    }

    /**
     * @param list<array<string, mixed>> $blocs
     * @param array<string, string>      $types
     */
    private function collecterTypes(array $blocs, array &$types): void
    {
        foreach ($blocs as $bloc) {
            $types[(string) $bloc['id']] = (string) $bloc['type'];
            $this->collecterTypes($bloc['children'] ?? [], $types);
        }
    }

    /** @param list<array<string, mixed>> $blocs */
    private function typeDe(array $blocs, string $id): ?string
    {
        $types = [];
        $this->collecterTypes($blocs, $types);

        return $types[$id] ?? null;
    }
}
