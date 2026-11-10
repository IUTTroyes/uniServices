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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\StatutAutoEvaluationEnum;
use UnifolioBundle\Repository\AutoEvaluationRepository;

/**
 * Positionnement de l'étudiant sur la grille de sa formation, soit :
 * - pour une trace : sur un de ses rattachements ;
 * - en bilan : sur un élément du référentiel de son année (compétence, niveau ou AC).
 * La version de grille est figée à la création.
 */
#[ORM\Entity(repositoryClass: AutoEvaluationRepository::class)]
#[ORM\Table(name: 'unifolio_auto_evaluation')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_auto_evaluation_uuid', columns: ['uuid'])]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_auto_evaluation_rattachement', columns: ['rattachement_id'])]
class AutoEvaluation implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Etudiant $etudiant;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private EtudiantScolarite $scolarite;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private GrilleVersion $grilleVersion;

    /** Auto-évaluation d'une trace sur un élément. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?TraceRattachement $rattachement = null;

    /** Bilan sur un élément du référentiel (une seule des trois cibles). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcCompetence $competence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcNiveau $niveau = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcApprentissageCritique $apprentissageCritique = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reflexion = null;

    #[ORM\Column(length: 20, enumType: StatutAutoEvaluationEnum::class, options: ['default' => 'brouillon'])]
    private StatutAutoEvaluationEnum $statut = StatutAutoEvaluationEnum::BROUILLON;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $soumiseLe = null;

    /** Score pondéré entre 0 et 1, recalculé à chaque enregistrement. */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $score = null;

    /** @var Collection<int, ReponseCritere> */
    #[ORM\OneToMany(targetEntity: ReponseCritere::class, mappedBy: 'autoEvaluation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reponses;

    private function __construct(Etudiant $etudiant, EtudiantScolarite $scolarite, GrilleVersion $version)
    {
        if (!$version->isPubliee()) {
            throw new \InvalidArgumentException('La grille de la formation n\'est pas publiée.');
        }
        $this->etudiant = $etudiant;
        $this->scolarite = $scolarite;
        $this->grilleVersion = $version;
        $this->reponses = new ArrayCollection();
        $this->setUuid();
    }

    public static function pourRattachement(TraceRattachement $rattachement, EtudiantScolarite $scolarite, GrilleVersion $version): self
    {
        $a = new self($rattachement->getTrace()->getEtudiant(), $scolarite, $version);
        $a->rattachement = $rattachement;

        return $a;
    }

    public static function bilan(
        Etudiant $etudiant,
        EtudiantScolarite $scolarite,
        GrilleVersion $version,
        ApcCompetence|ApcNiveau|ApcApprentissageCritique $cible,
    ): self {
        $a = new self($etudiant, $scolarite, $version);
        match (true) {
            $cible instanceof ApcCompetence => $a->competence = $cible,
            $cible instanceof ApcNiveau => $a->niveau = $cible,
            $cible instanceof ApcApprentissageCritique => $a->apprentissageCritique = $cible,
        };

        return $a;
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
            ?? throw new \LogicException('Auto-évaluation sans cible.');
    }

    public function getCompetenceParente(): ?ApcCompetence
    {
        return $this->rattachement?->getCompetenceParente()
            ?? $this->competence
            ?? $this->niveau?->getCompetence()
            ?? $this->apprentissageCritique?->getNiveau()?->getCompetence();
    }

    public function getReflexion(): ?string
    {
        return $this->reflexion;
    }

    public function setReflexion(?string $reflexion): static
    {
        $this->assertModifiable();
        $this->reflexion = $reflexion !== null && trim($reflexion) !== '' ? $reflexion : null;

        return $this;
    }

    public function getStatut(): StatutAutoEvaluationEnum
    {
        return $this->statut;
    }

    public function isModifiable(): bool
    {
        return $this->statut === StatutAutoEvaluationEnum::BROUILLON;
    }

    public function getSoumiseLe(): ?\DateTimeImmutable
    {
        return $this->soumiseLe;
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

    /**
     * Critères proposés à l'étudiant (cochés « utilisé en auto-évaluation »).
     *
     * @return GrilleCritere[]
     */
    public function getCriteres(): array
    {
        return array_values(array_filter(
            $this->grilleVersion->getCriteres()->toArray(),
            static fn (GrilleCritere $c) => $c->isUtiliseAutoEval()
        ));
    }

    /**
     * Remplace les réponses. Les critères inconnus ou hors auto-évaluation sont refusés.
     *
     * @param array<int|string, array<string, mixed>> $saisies indexées par identifiant de critère
     */
    public function repondre(array $saisies): void
    {
        $this->assertModifiable();
        $reponses = ReponseCritere::depuisSaisies($this->getCriteres(), $saisies);

        $this->reponses->clear();
        foreach ($reponses as $reponse) {
            $reponse->attacherA($this);
            $this->reponses->add($reponse);
        }
        $this->score = ReponseCritere::calculerScore($reponses);
    }

    /** @return string[] */
    public function getErreursSoumission(): array
    {
        $manquants = [];
        foreach ($this->getCriteres() as $critere) {
            if ($critere->isObligatoire() && $this->getReponsePour($critere) === null) {
                $manquants[] = $critere->getLibelle();
            }
        }

        return $manquants === [] ? [] : ['Répondez aux critères obligatoires : ' . implode(', ', $manquants) . '.'];
    }

    public function soumettre(?\DateTimeImmutable $le = null): void
    {
        $this->assertModifiable();
        $erreurs = $this->getErreursSoumission();
        if ($erreurs !== []) {
            throw new \DomainException(implode(' ', $erreurs));
        }
        $this->statut = StatutAutoEvaluationEnum::SOUMISE;
        $this->soumiseLe = $le ?? new \DateTimeImmutable();
    }

    /** Retour en saisie (tant qu'aucune évaluation enseignante ne s'appuie dessus — contrôle au lot 4). */
    public function reprendre(): void
    {
        $this->statut = StatutAutoEvaluationEnum::BROUILLON;
        $this->soumiseLe = null;
    }

    private function assertModifiable(): void
    {
        if (!$this->isModifiable()) {
            throw new \DomainException('Cette auto-évaluation est soumise : repassez-la en saisie pour la modifier.');
        }
    }
}
