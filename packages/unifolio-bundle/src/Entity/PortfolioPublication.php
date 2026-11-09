<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\StatutRenduEnum;
use UnifolioBundle\Repository\PortfolioPublicationRepository;

/**
 * Version publiée d'un portfolio : instantané immuable (pages, blocs et données résolues) + fichiers HTML générés.
 * Une seule publication est « active » par portfolio ; les précédentes restent en historique (borné).
 * La visibilité n'est PAS figée ici : elle est lue sur le portfolio à chaque requête, pour qu'un retour en « privé »
 * coupe l'accès immédiatement.
 */
#[ORM\Entity(repositoryClass: PortfolioPublicationRepository::class)]
#[ORM\Table(name: 'unifolio_portfolio_publication')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_publication_uuid', columns: ['uuid'])]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_publication_numero', columns: ['portfolio_id', 'numero'])]
#[ORM\Index(name: 'idx_unifolio_publication_active', columns: ['portfolio_id', 'active'])]
class PortfolioPublication implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: null)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Portfolio $portfolio;

    #[ORM\Column]
    private int $numero;

    #[ORM\Column(length: 20, enumType: StatutRenduEnum::class)]
    private StatutRenduEnum $statut = StatutRenduEnum::EN_ATTENTE;

    #[ORM\Column(options: ['default' => false])]
    private bool $active = false;

    /** Instantané : { portfolio, pages: [{slug, titre, blocs}], donnees }. */
    #[ORM\Column(type: Types::JSON)]
    private array $instantane = [];

    /** Fichiers générés (relatifs au dossier de la publication), pour le service et l'export. */
    #[ORM\Column(type: Types::JSON)]
    private array $fichiers = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $erreur = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $genereLe = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $desactiveeLe = null;

    /** Motif de la modération (publication retirée par un référent) ; non nul = bloquée tant qu'elle n'est pas rétablie. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifModeration = null;

    public function __construct(Portfolio $portfolio, int $numero, array $instantane)
    {
        $this->portfolio = $portfolio;
        $this->numero = $numero;
        $this->instantane = $instantane;
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

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function getStatut(): StatutRenduEnum
    {
        return $this->statut;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getInstantane(): array
    {
        return $this->instantane;
    }

    /** @return list<string> */
    public function getFichiers(): array
    {
        return $this->fichiers;
    }

    public function getErreur(): ?string
    {
        return $this->erreur;
    }

    public function getGenereLe(): ?\DateTimeImmutable
    {
        return $this->genereLe;
    }

    public function getDesactiveeLe(): ?\DateTimeImmutable
    {
        return $this->desactiveeLe;
    }

    public function getMotifModeration(): ?string
    {
        return $this->motifModeration;
    }

    public function isModeree(): bool
    {
        return $this->motifModeration !== null;
    }

    /** @param list<string> $fichiers */
    public function marquerGeneree(array $fichiers): static
    {
        $this->statut = StatutRenduEnum::GENERE;
        $this->fichiers = array_values($fichiers);
        $this->erreur = null;
        $this->genereLe = new \DateTimeImmutable();

        return $this;
    }

    public function marquerEnErreur(string $message): static
    {
        $this->statut = StatutRenduEnum::ERREUR;
        $this->erreur = mb_substr($message, 0, 2000);

        return $this;
    }

    /** Active cette publication (seule une publication générée peut l'être). */
    public function activer(): static
    {
        if ($this->statut !== StatutRenduEnum::GENERE) {
            throw new \DomainException('Seule une publication générée peut être mise en ligne.');
        }
        $this->active = true;
        $this->desactiveeLe = null;

        return $this;
    }

    public function desactiver(): static
    {
        if ($this->active) {
            $this->active = false;
            $this->desactiveeLe = new \DateTimeImmutable();
        }

        return $this;
    }

    public function moderer(string $motif): static
    {
        $motif = trim($motif);
        if ($motif === '') {
            throw new \InvalidArgumentException('Le motif de la modération est obligatoire.');
        }
        $this->motifModeration = mb_substr($motif, 0, 1000);

        return $this;
    }

    public function retablir(): static
    {
        $this->motifModeration = null;

        return $this;
    }

    public function aFichier(string $fichier): bool
    {
        return \in_array($fichier, $this->fichiers, true);
    }
}
