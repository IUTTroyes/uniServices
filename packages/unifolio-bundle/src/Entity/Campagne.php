<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\PhaseCampagneEnum;
use UnifolioBundle\Repository\CampagneRepository;

/**
 * Calendrier de dépôt et d'évaluation d'une formation BUT pour une année universitaire
 * (éventuellement limité à une année de BUT).
 */
#[ORM\Entity(repositoryClass: CampagneRepository::class)]
#[ORM\Table(name: 'unifolio_campagne')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_campagne_uuid', columns: ['uuid'])]
class Campagne implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureDiplome $diplome;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureAnneeUniversitaire $anneeUniversitaire;

    /** Année de BUT visée (BUT1, BUT2…) ; null = toutes les années du diplôme. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?StructureAnnee $anneeFormation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $ouvertureLe;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $depotLimite = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $evaluationLimite = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $clotureLe = null;

    /** Si vrai, un enseignant ne peut évaluer que ce qui a été auto-évalué (et soumis) par l'étudiant. */
    #[ORM\Column(options: ['default' => false])]
    private bool $autoEvaluationRequise = false;

    public function __construct(string $libelle, StructureDiplome $diplome, StructureAnneeUniversitaire $anneeUniversitaire, \DateTimeImmutable $ouvertureLe)
    {
        if ($diplome->getTypeDiplome()?->isApc() !== true) {
            throw new \InvalidArgumentException('Les campagnes UniFolio sont réservées aux diplômes APC (BUT).');
        }
        $this->setLibelle($libelle);
        $this->diplome = $diplome;
        $this->anneeUniversitaire = $anneeUniversitaire;
        $this->ouvertureLe = $ouvertureLe;
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
            throw new \InvalidArgumentException('Le libellé de la campagne est obligatoire.');
        }
        $this->libelle = mb_substr($libelle, 0, 255);

        return $this;
    }

    public function getDiplome(): StructureDiplome
    {
        return $this->diplome;
    }

    public function getAnneeUniversitaire(): StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function getAnneeFormation(): ?StructureAnnee
    {
        return $this->anneeFormation;
    }

    public function setAnneeFormation(?StructureAnnee $anneeFormation): static
    {
        if ($anneeFormation !== null && $anneeFormation->getDiplome()?->getId() !== $this->diplome->getId()) {
            throw new \InvalidArgumentException('Cette année de BUT n\'appartient pas au diplôme de la campagne.');
        }
        $this->anneeFormation = $anneeFormation;

        return $this;
    }

    public function getOuvertureLe(): \DateTimeImmutable
    {
        return $this->ouvertureLe;
    }

    public function getDepotLimite(): ?\DateTimeImmutable
    {
        return $this->depotLimite;
    }

    public function getEvaluationLimite(): ?\DateTimeImmutable
    {
        return $this->evaluationLimite;
    }

    public function getClotureLe(): ?\DateTimeImmutable
    {
        return $this->clotureLe;
    }

    /** Les dates renseignées doivent se suivre : ouverture ≤ limite de dépôt ≤ limite d'évaluation ≤ clôture. */
    public function definirCalendrier(
        \DateTimeImmutable $ouvertureLe,
        ?\DateTimeImmutable $depotLimite,
        ?\DateTimeImmutable $evaluationLimite,
        ?\DateTimeImmutable $clotureLe,
    ): static {
        $precedente = $ouvertureLe;
        foreach (['la limite de dépôt' => $depotLimite, 'la limite d\'évaluation' => $evaluationLimite, 'la clôture' => $clotureLe] as $nom => $date) {
            if ($date === null) {
                continue;
            }
            if ($date < $precedente) {
                throw new \InvalidArgumentException(sprintf('Les dates doivent se suivre : %s est antérieure à la date précédente.', $nom));
            }
            $precedente = $date;
        }
        $this->ouvertureLe = $ouvertureLe;
        $this->depotLimite = $depotLimite;
        $this->evaluationLimite = $evaluationLimite;
        $this->clotureLe = $clotureLe;

        return $this;
    }

    public function cloturer(?\DateTimeImmutable $le = null): static
    {
        $le ??= new \DateTimeImmutable();
        $this->clotureLe = $le;
        foreach (['depotLimite', 'evaluationLimite'] as $champ) {
            if ($this->$champ !== null && $this->$champ > $le) {
                $this->$champ = $le;
            }
        }

        return $this;
    }

    public function isAutoEvaluationRequise(): bool
    {
        return $this->autoEvaluationRequise;
    }

    public function setAutoEvaluationRequise(bool $autoEvaluationRequise): static
    {
        $this->autoEvaluationRequise = $autoEvaluationRequise;

        return $this;
    }

    public function getPhase(?\DateTimeImmutable $maintenant = null): PhaseCampagneEnum
    {
        $maintenant ??= new \DateTimeImmutable();

        return match (true) {
            $this->clotureLe !== null && $maintenant >= $this->clotureLe => PhaseCampagneEnum::CLOTUREE,
            $maintenant < $this->ouvertureLe => PhaseCampagneEnum::A_VENIR,
            $this->depotLimite !== null && $maintenant > $this->depotLimite => PhaseCampagneEnum::EVALUATION,
            default => PhaseCampagneEnum::DEPOT,
        };
    }

    public function isCloturee(?\DateTimeImmutable $maintenant = null): bool
    {
        return $this->getPhase($maintenant) === PhaseCampagneEnum::CLOTUREE;
    }

    /** La limite d'évaluation est dépassée sans que la campagne soit clôturée. */
    public function isEvaluationEnRetard(?\DateTimeImmutable $maintenant = null): bool
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->evaluationLimite !== null && $maintenant > $this->evaluationLimite && !$this->isCloturee($maintenant);
    }
}
