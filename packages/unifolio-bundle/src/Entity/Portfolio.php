<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\TypePortfolioEnum;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;
use UnifolioBundle\Repository\PortfolioRepository;

/**
 * Portfolio d'un étudiant : un portfolio académique par année de BUT (évalué), ou une vitrine (lot 6).
 * Même moteur dans les deux cas : des pages composées de blocs.
 */
#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
#[ORM\Table(name: 'unifolio_portfolio')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_portfolio_uuid', columns: ['uuid'])]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_portfolio_scolarite', columns: ['etudiant_id', 'scolarite_id', 'type'])]
class Portfolio implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    public const PAGES_MAX = 30;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Etudiant $etudiant;

    /** Année de BUT d'un portfolio académique ; null pour une vitrine. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?EtudiantScolarite $scolarite = null;

    #[ORM\Column(length: 20, enumType: TypePortfolioEnum::class)]
    private TypePortfolioEnum $type;

    #[ORM\Column(length: 255)]
    private string $titre;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $theme = [];

    #[ORM\Column(length: 20, enumType: VisibilitePortfolioEnum::class, options: ['default' => 'prive'])]
    private VisibilitePortfolioEnum $visibilite = VisibilitePortfolioEnum::PRIVE;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PortfolioModele $modele = null;

    /** @var Collection<int, PortfolioPage> */
    #[ORM\OneToMany(targetEntity: PortfolioPage::class, mappedBy: 'portfolio', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $pages;

    public function __construct(Etudiant $etudiant, TypePortfolioEnum $type, string $titre, ?EtudiantScolarite $scolarite = null, ?PortfolioModele $modele = null)
    {
        if ($type === TypePortfolioEnum::ACADEMIQUE && $scolarite === null) {
            throw new \InvalidArgumentException('Un portfolio académique est rattaché à une année de BUT.');
        }
        $this->etudiant = $etudiant;
        $this->type = $type;
        $this->scolarite = $type === TypePortfolioEnum::ACADEMIQUE ? $scolarite : null;
        $this->modele = $modele;
        $this->setTitre($titre);
        $this->pages = new ArrayCollection();
        $this->setUuid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiant(): Etudiant
    {
        return $this->etudiant;
    }

    public function appartientA(Etudiant $etudiant): bool
    {
        return $this->etudiant === $etudiant
            || ($this->etudiant->getId() !== null && $this->etudiant->getId() === $etudiant->getId());
    }

    public function getScolarite(): ?EtudiantScolarite
    {
        return $this->scolarite;
    }

    public function getType(): TypePortfolioEnum
    {
        return $this->type;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $titre = trim($titre);
        if ($titre === '') {
            throw new \InvalidArgumentException('Le titre du portfolio est obligatoire.');
        }
        $this->titre = mb_substr($titre, 0, 255);

        return $this;
    }

    /** @return array<string, mixed> */
    public function getTheme(): array
    {
        return $this->theme;
    }

    /** @param array<string, mixed> $theme */
    public function setTheme(array $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getVisibilite(): VisibilitePortfolioEnum
    {
        return $this->visibilite;
    }

    public function setVisibilite(VisibilitePortfolioEnum $visibilite): static
    {
        if (!$this->type->autoriseVisibilite($visibilite)) {
            throw new \InvalidArgumentException(sprintf('Un %s ne peut pas être « %s ».', mb_strtolower($this->type->getLibelle()), mb_strtolower($visibilite->getLibelle())));
        }
        $this->visibilite = $visibilite;

        return $this;
    }

    public function getModele(): ?PortfolioModele
    {
        return $this->modele;
    }

    /** @return Collection<int, PortfolioPage> */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function getPage(string $uuid): ?PortfolioPage
    {
        foreach ($this->pages as $page) {
            if ($page->getUuidString() === strtolower($uuid)) {
                return $page;
            }
        }

        return null;
    }

    public function ajouterPage(string $titre, bool $verrouillee = false): PortfolioPage
    {
        if ($this->pages->count() >= self::PAGES_MAX) {
            throw new \DomainException(sprintf('Un portfolio est limité à %d pages.', self::PAGES_MAX));
        }
        $ordre = -1;
        foreach ($this->pages as $p) {
            $ordre = max($ordre, $p->getOrdre());
        }
        $page = new PortfolioPage($this, $titre, $ordre + 1, $verrouillee);
        $this->pages->add($page);

        return $page;
    }

    public function retirerPage(PortfolioPage $page): void
    {
        if ($page->isVerrouillee()) {
            throw new \DomainException('Cette page est imposée par le modèle : elle ne peut pas être supprimée.');
        }
        $this->pages->removeElement($page);
    }

    /** @param list<string> $uuids nouvel ordre des pages */
    public function reordonnerPages(array $uuids): void
    {
        $position = array_flip(array_map('strtolower', array_values($uuids)));
        $suivant = \count($position);
        foreach ($this->pages as $page) {
            $page->setOrdre($position[$page->getUuidString()] ?? $suivant++);
        }
    }
}
