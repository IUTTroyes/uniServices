<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\ConceptionIaEnum;
use UnifolioBundle\Enum\RealisationIaEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Enum\TypeActiviteEnum;
use UnifolioBundle\Repository\TraceRepository;

/**
 * Preuve déposée par un étudiant. Rattachée à la scolarité de l'année de réalisation,
 * elle reste réutilisable les années suivantes (bibliothèque globale).
 */
#[ORM\Entity(repositoryClass: TraceRepository::class)]
#[ORM\Table(name: 'unifolio_trace')]
#[ORM\Index(name: 'idx_unifolio_trace_etudiant_statut', columns: ['etudiant_id', 'statut'])]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_trace_uuid', columns: ['uuid'])]
class Trace implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    public const TITRE_MAX = 255;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Etudiant $etudiant;

    /** Scolarité (année de BUT) pendant laquelle la trace a été réalisée : borne le référentiel proposé. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?EtudiantScolarite $scolarite = null;

    #[ORM\Column(length: self::TITRE_MAX)]
    private string $titre;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contexte = null;

    /** Type « date » (surchargé par Carbon dans le cœur) : évite un écart de schéma perpétuel. */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRealisation = null;

    #[ORM\Column(length: 30, nullable: true, enumType: TypeActiviteEnum::class)]
    private ?TypeActiviteEnum $typeActivite = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ScolEnseignement $enseignement = null;

    #[ORM\Column(type: Types::SMALLINT, enumType: ConceptionIaEnum::class, options: ['default' => 3])]
    private ConceptionIaEnum $conceptionIa = ConceptionIaEnum::NON_RENSEIGNE;

    #[ORM\Column(type: Types::SMALLINT, enumType: RealisationIaEnum::class, options: ['default' => 4])]
    private RealisationIaEnum $realisationIa = RealisationIaEnum::NON_RENSEIGNE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $usageIaDetail = null;

    #[ORM\Column(length: 20, enumType: StatutTraceEnum::class, options: ['default' => 'brouillon'])]
    private StatutTraceEnum $statut = StatutTraceEnum::BROUILLON;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deposeeLe = null;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON)]
    private array $tags = [];

    /** @var Collection<int, TraceMedia> */
    #[ORM\OneToMany(targetEntity: TraceMedia::class, mappedBy: 'trace', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $medias;

    /** @var Collection<int, TraceRattachement> */
    #[ORM\OneToMany(targetEntity: TraceRattachement::class, mappedBy: 'trace', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $rattachements;

    public function __construct(Etudiant $etudiant, string $titre, ?EtudiantScolarite $scolarite = null)
    {
        $this->etudiant = $etudiant;
        $this->setTitre($titre);
        $this->scolarite = $scolarite;
        $this->medias = new ArrayCollection();
        $this->rattachements = new ArrayCollection();
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

    public function setScolarite(?EtudiantScolarite $scolarite): static
    {
        $this->scolarite = $scolarite;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $titre = trim($titre);
        if ($titre === '') {
            throw new \InvalidArgumentException('Le titre de la trace est obligatoire.');
        }
        $this->titre = mb_substr($titre, 0, self::TITRE_MAX);

        return $this;
    }

    public function getContexte(): ?string
    {
        return $this->contexte;
    }

    public function setContexte(?string $contexte): static
    {
        $this->contexte = $contexte !== null && trim($contexte) !== '' ? $contexte : null;

        return $this;
    }

    public function getDateRealisation(): ?\DateTimeImmutable
    {
        return $this->dateRealisation !== null ? \DateTimeImmutable::createFromInterface($this->dateRealisation) : null;
    }

    public function setDateRealisation(?\DateTimeInterface $dateRealisation): static
    {
        $this->dateRealisation = $dateRealisation !== null ? \DateTime::createFromInterface($dateRealisation) : null;

        return $this;
    }

    public function getTypeActivite(): ?TypeActiviteEnum
    {
        return $this->typeActivite;
    }

    public function setTypeActivite(?TypeActiviteEnum $typeActivite): static
    {
        $this->typeActivite = $typeActivite;

        return $this;
    }

    public function getEnseignement(): ?ScolEnseignement
    {
        return $this->enseignement;
    }

    public function setEnseignement(?ScolEnseignement $enseignement): static
    {
        $this->enseignement = $enseignement;

        return $this;
    }

    public function getConceptionIa(): ConceptionIaEnum
    {
        return $this->conceptionIa;
    }

    public function setConceptionIa(ConceptionIaEnum $conceptionIa): static
    {
        $this->conceptionIa = $conceptionIa;

        return $this;
    }

    public function getRealisationIa(): RealisationIaEnum
    {
        return $this->realisationIa;
    }

    public function setRealisationIa(RealisationIaEnum $realisationIa): static
    {
        $this->realisationIa = $realisationIa;

        return $this;
    }

    public function getUsageIaDetail(): ?string
    {
        return $this->usageIaDetail;
    }

    public function setUsageIaDetail(?string $usageIaDetail): static
    {
        $this->usageIaDetail = $usageIaDetail !== null && trim($usageIaDetail) !== '' ? $usageIaDetail : null;

        return $this;
    }

    public function getStatut(): StatutTraceEnum
    {
        return $this->statut;
    }

    public function isModifiable(): bool
    {
        return $this->statut->isModifiableParEtudiant();
    }

    public function getDeposeeLe(): ?\DateTimeImmutable
    {
        return $this->deposeeLe;
    }

    /** @return string[] */
    public function getTags(): array
    {
        return $this->tags;
    }

    /** @param string[] $tags */
    public function setTags(array $tags): static
    {
        $propres = [];
        foreach ($tags as $tag) {
            $tag = mb_strtolower(trim((string) $tag));
            if ($tag !== '' && mb_strlen($tag) <= 50) {
                $propres[$tag] = $tag;
            }
        }
        $this->tags = array_values(\array_slice($propres, 0, 20));

        return $this;
    }

    /**
     * Conditions pour déposer la trace (passage en évaluation possible).
     * D6 : la déclaration d'usage de l'IA est obligatoire au dépôt.
     *
     * @return string[] messages d'erreur, vide si la trace peut être déposée
     */
    public function getErreursDepot(): array
    {
        $erreurs = [];
        if ($this->medias->isEmpty()) {
            $erreurs[] = 'Ajoutez au moins une preuve (fichier, lien ou texte).';
        }
        if ($this->rattachements->isEmpty()) {
            $erreurs[] = 'Rattachez la trace à au moins un élément du référentiel.';
        }
        if (!$this->conceptionIa->isRenseigne() || !$this->realisationIa->isRenseigne()) {
            $erreurs[] = 'Déclarez l\'usage de l\'IA (conception et réalisation).';
        }

        return $erreurs;
    }

    public function deposer(?\DateTimeImmutable $le = null): void
    {
        $erreurs = $this->getErreursDepot();
        if ($erreurs !== []) {
            throw new \DomainException(implode(' ', $erreurs));
        }
        $this->changerStatut(StatutTraceEnum::DEPOSEE);
        $this->deposeeLe = $le ?? new \DateTimeImmutable();
    }

    public function retirerEnBrouillon(): void
    {
        $this->changerStatut(StatutTraceEnum::BROUILLON);
        $this->deposeeLe = null;
    }

    public function changerStatut(StatutTraceEnum $cible): void
    {
        if (!$this->statut->peutPasserA($cible)) {
            throw new \DomainException(sprintf(
                'Une trace « %s » ne peut pas passer à « %s ».',
                $this->statut->getLibelle(),
                $cible->getLibelle()
            ));
        }
        $this->statut = $cible;
    }

    /** @return Collection<int, TraceMedia> */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(TraceMedia $media): static
    {
        if (!$this->medias->contains($media)) {
            $media->setOrdre($this->prochainOrdre($this->medias));
            $this->medias->add($media);
        }

        return $this;
    }

    public function removeMedia(TraceMedia $media): static
    {
        $this->medias->removeElement($media);

        return $this;
    }

    public function getMediaParId(int $id): ?TraceMedia
    {
        foreach ($this->medias as $media) {
            if ($media->getId() === $id) {
                return $media;
            }
        }

        return null;
    }

    /** @param int[] $ids nouvel ordre des médias, par identifiant */
    public function reordonnerMedias(array $ids): void
    {
        $position = array_flip(array_values(array_map('intval', $ids)));
        $suivant = \count($position);
        foreach ($this->medias as $media) {
            $media->setOrdre($position[$media->getId()] ?? $suivant++);
        }
    }

    /** @return Collection<int, TraceRattachement> */
    public function getRattachements(): Collection
    {
        return $this->rattachements;
    }

    public function addRattachement(TraceRattachement $rattachement): static
    {
        foreach ($this->rattachements as $existant) {
            if ($existant->getCleCible() === $rattachement->getCleCible()) {
                $existant->setArgumentaire($rattachement->getArgumentaire());

                return $this;
            }
        }
        $rattachement->setOrdre($this->prochainOrdre($this->rattachements));
        $this->rattachements->add($rattachement);

        return $this;
    }

    /**
     * Remplace l'ensemble des rattachements en conservant ceux qui existent déjà
     * (et donc leurs futures évaluations).
     *
     * @param TraceRattachement[] $nouveaux
     */
    public function remplacerRattachements(array $nouveaux): void
    {
        $cles = [];
        foreach ($nouveaux as $ordre => $nouveau) {
            $cles[$nouveau->getCleCible()] = [$nouveau, $ordre];
        }

        foreach ($this->rattachements->toArray() as $existant) {
            if (!isset($cles[$existant->getCleCible()])) {
                $this->rattachements->removeElement($existant);
            }
        }

        foreach ($cles as $cle => [$nouveau, $ordre]) {
            $existant = null;
            foreach ($this->rattachements as $r) {
                if ($r->getCleCible() === $cle) {
                    $existant = $r;
                    break;
                }
            }
            if ($existant !== null) {
                $existant->setArgumentaire($nouveau->getArgumentaire());
                $existant->setOrdre($ordre);
            } else {
                $nouveau->setOrdre($ordre);
                $this->rattachements->add($nouveau);
            }
        }
    }

    /** @param Collection<int, TraceMedia|TraceRattachement> $collection */
    private function prochainOrdre(Collection $collection): int
    {
        $max = -1;
        foreach ($collection as $element) {
            $max = max($max, $element->getOrdre());
        }

        return $max + 1;
    }
}
