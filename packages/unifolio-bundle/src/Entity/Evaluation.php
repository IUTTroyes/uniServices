<?php

namespace UnifolioBundle\Entity;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Repository\EvaluationRepository;

/**
 * Évaluation d'un enseignant sur la grille de la formation, soit :
 * - d'une trace, sur un de ses rattachements ;
 * - en bilan, sur un élément du référentiel de l'année de l'étudiant.
 * La version de grille est figée à la création. Plusieurs évaluateurs peuvent évaluer la même cible.
 */
#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[ORM\Table(name: 'unifolio_evaluation')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_evaluation_uuid', columns: ['uuid'])]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_evaluation_rattachement', columns: ['evaluateur_id', 'rattachement_id'])]
class Evaluation implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Campagne $campagne;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Personnel $evaluateur;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Etudiant $etudiant;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private EtudiantScolarite $scolarite;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private GrilleVersion $grilleVersion;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?TraceRattachement $rattachement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcCompetence $competence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcNiveau $niveau = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcApprentissageCritique $apprentissageCritique = null;

    /** Appréciation générale ; motif de la demande de révision le cas échéant. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $appreciation = null;

    #[ORM\Column(length: 20, enumType: StatutEvaluationEnum::class, options: ['default' => 'en_cours'])]
    private StatutEvaluationEnum $statut = StatutEvaluationEnum::EN_COURS;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $rendueLe = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $visibleEtudiant = false;

    /** Score pondéré entre 0 et 1, recalculé à chaque enregistrement. */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $score = null;

    /** @var Collection<int, ReponseCritere> */
    #[ORM\OneToMany(targetEntity: ReponseCritere::class, mappedBy: 'evaluation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reponses;

    private function __construct(Campagne $campagne, Personnel $evaluateur, Etudiant $etudiant, EtudiantScolarite $scolarite, GrilleVersion $version)
    {
        if (!$version->isPubliee()) {
            throw new \InvalidArgumentException('La grille de la formation n\'est pas publiée.');
        }
        $this->campagne = $campagne;
        $this->evaluateur = $evaluateur;
        $this->etudiant = $etudiant;
        $this->scolarite = $scolarite;
        $this->grilleVersion = $version;
        $this->reponses = new ArrayCollection();
        $this->setUuid();
    }

    public static function pourRattachement(Campagne $campagne, Personnel $evaluateur, TraceRattachement $rattachement, EtudiantScolarite $scolarite, GrilleVersion $version): self
    {
        $e = new self($campagne, $evaluateur, $rattachement->getTrace()->getEtudiant(), $scolarite, $version);
        $e->rattachement = $rattachement;

        return $e;
    }

    public static function bilan(
        Campagne $campagne,
        Personnel $evaluateur,
        Etudiant $etudiant,
        EtudiantScolarite $scolarite,
        GrilleVersion $version,
        ApcCompetence|ApcNiveau|ApcApprentissageCritique $cible,
    ): self {
        $e = new self($campagne, $evaluateur, $etudiant, $scolarite, $version);
        match (true) {
            $cible instanceof ApcCompetence => $e->competence = $cible,
            $cible instanceof ApcNiveau => $e->niveau = $cible,
            $cible instanceof ApcApprentissageCritique => $e->apprentissageCritique = $cible,
        };

        return $e;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampagne(): Campagne
    {
        return $this->campagne;
    }

    public function getEvaluateur(): Personnel
    {
        return $this->evaluateur;
    }

    public function estDe(Personnel $personnel): bool
    {
        return $this->evaluateur === $personnel
            || ($this->evaluateur->getId() !== null && $this->evaluateur->getId() === $personnel->getId());
    }

    public function getEtudiant(): Etudiant
    {
        return $this->etudiant;
    }

    public function getScolarite(): EtudiantScolarite
    {
        return $this->scolarite;
    }

    public function getGrilleVersion(): GrilleVersion
    {
        return $this->grilleVersion;
    }

    public function getRattachement(): ?TraceRattachement
    {
        return $this->rattachement;
    }

    public function isBilan(): bool
    {
        return $this->rattachement === null;
    }

    public function getGranularite(): GranulariteEnum
    {
        if ($this->rattachement !== null) {
            return $this->rattachement->getGranularite();
        }

        return match (true) {
            $this->apprentissageCritique !== null => GranulariteEnum::AC,
            $this->niveau !== null => GranulariteEnum::NIVEAU,
            default => GranulariteEnum::COMPETENCE,
        };
    }

    public function getCible(): ApcCompetence|ApcNiveau|ApcApprentissageCritique
    {
        return $this->rattachement?->getCible()
            ?? $this->apprentissageCritique ?? $this->niveau ?? $this->competence
            ?? throw new \LogicException('Évaluation sans cible.');
    }

    public function getCompetenceParente(): ?ApcCompetence
    {
        return $this->rattachement?->getCompetenceParente()
            ?? $this->competence
            ?? $this->niveau?->getCompetence()
            ?? $this->apprentissageCritique?->getNiveau()?->getCompetence();
    }

    public function getAppreciation(): ?string
    {
        return $this->appreciation;
    }

    public function getStatut(): StatutEvaluationEnum
    {
        return $this->statut;
    }

    public function isModifiable(): bool
    {
        return $this->statut === StatutEvaluationEnum::EN_COURS;
    }

    public function getRendueLe(): ?\DateTimeImmutable
    {
        return $this->rendueLe;
    }

    public function isVisibleEtudiant(): bool
    {
        return $this->visibleEtudiant;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    /** @return Collection<int, ReponseCritere> */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function getReponsePour(GrilleCritere $critere): ?ReponseCritere
    {
        foreach ($this->reponses as $reponse) {
            if ($reponse->getCritere() === $critere) {
                return $reponse;
            }
        }

        return null;
    }

    /** @return GrilleCritere[] tous les critères de la version (l'évaluateur voit toute la grille) */
    public function getCriteres(): array
    {
        return $this->grilleVersion->getCriteres()->toArray();
    }

    /**
     * @param array{appreciation?: ?string, reponses?: array<int|string, array<string, mixed>>} $d
     */
    public function renseigner(array $d): void
    {
        $this->assertModifiable();
        if (\array_key_exists('appreciation', $d)) {
            $a = $d['appreciation'];
            $this->appreciation = $a !== null && trim((string) $a) !== '' ? (string) $a : null;
        }
        if (\array_key_exists('reponses', $d)) {
            $reponses = ReponseCritere::depuisSaisies($this->getCriteres(), $d['reponses']);
            $this->reponses->clear();
            foreach ($reponses as $reponse) {
                $reponse->attacherA($this);
                $this->reponses->add($reponse);
            }
            $this->score = ReponseCritere::calculerScore($reponses);
        }
    }

    /** @return string[] */
    public function getErreursRendu(): array
    {
        $manquants = [];
        foreach ($this->getCriteres() as $critere) {
            if ($critere->isObligatoire() && $this->getReponsePour($critere) === null) {
                $manquants[] = $critere->getLibelle();
            }
        }

        return $manquants === [] ? [] : ['Répondez aux critères obligatoires : ' . implode(', ', $manquants) . '.'];
    }

    public function rendre(bool $visibleEtudiant = true, ?\DateTimeImmutable $le = null): void
    {
        $this->assertModifiable();
        $erreurs = $this->getErreursRendu();
        if ($erreurs !== []) {
            throw new \DomainException(implode(' ', $erreurs));
        }
        $this->statut = StatutEvaluationEnum::RENDUE;
        $this->rendueLe = $le ?? new \DateTimeImmutable();
        $this->visibleEtudiant = $visibleEtudiant;
    }

    /** L'évaluateur demande à l'étudiant de réviser sa trace ; le motif est obligatoire. */
    public function demanderRevision(string $motif, ?\DateTimeImmutable $le = null): void
    {
        $this->assertModifiable();
        if ($this->isBilan()) {
            throw new \DomainException('Une révision se demande sur une trace, pas sur un bilan.');
        }
        $motif = trim($motif);
        if ($motif === '') {
            throw new \DomainException('Expliquez à l\'étudiant ce qu\'il doit réviser.');
        }
        $this->appreciation = $motif;
        $this->statut = StatutEvaluationEnum::REVISION_DEMANDEE;
        $this->rendueLe = $le ?? new \DateTimeImmutable();
        $this->visibleEtudiant = true;
    }

    public function rouvrir(): void
    {
        $this->statut = StatutEvaluationEnum::EN_COURS;
        $this->rendueLe = null;
        $this->visibleEtudiant = false;
    }

    public function setVisibleEtudiant(bool $visible): static
    {
        if ($this->statut === StatutEvaluationEnum::EN_COURS && $visible) {
            throw new \DomainException('Une évaluation en cours n\'est pas encore communicable à l\'étudiant.');
        }
        $this->visibleEtudiant = $visible;

        return $this;
    }

    private function assertModifiable(): void
    {
        if (!$this->isModifiable()) {
            throw new \DomainException('Cette évaluation est rendue : rouvrez-la pour la modifier.');
        }
    }
}
