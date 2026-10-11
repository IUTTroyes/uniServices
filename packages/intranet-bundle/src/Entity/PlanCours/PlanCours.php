<?php

namespace IntranetBundle\Entity\PlanCours;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Users\Personnel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use IntranetBundle\Enum\PlanCours\PlanCoursEnum;
use IntranetBundle\Filter\PlanCours\PlanCoursFilter;
use IntranetBundle\Repository\PlanCours\PlanCoursRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PlanCoursRepository::class)]
#[ORM\Table(name: 'plan_cours')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['plancours:read', 'plancours:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['plancours:read', 'plancours:list']]),
        new Post(
            normalizationContext: ['groups' => ['plancours:read']],
            denormalizationContext: ['groups' => ['plancours:write']]
        ),
        new Patch(
            normalizationContext: ['groups' => ['plancours:read']],
            denormalizationContext: ['groups' => ['plancours:write']]
        ),
        new Delete(),
    ],
    paginationEnabled: false
)]
#[ApiFilter(PlanCoursFilter::class)]
class PlanCours implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['plancours:read', 'plancours:list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ScolEnseignement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['plancours:read', 'plancours:write', 'plancours:list'])]
    private ?ScolEnseignement $enseignement = null;

    #[ORM\ManyToOne(targetEntity: StructureAnneeUniversitaire::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['plancours:read', 'plancours:write', 'plancours:list'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['plancours:read', 'plancours:write', 'plancours:list'])]
    private ?Personnel $responsable = null;

    /**
     * @var Collection<int, Personnel>
     */
    #[ORM\ManyToMany(targetEntity: Personnel::class)]
    #[ORM\JoinTable(name: 'plan_cours_intervenants')]
    #[Groups(['plancours:read', 'plancours:write'])]
    private Collection $intervenants;

    #[ORM\Column(length: 30, enumType: PlanCoursEnum::class)]
    #[Groups(['plancours:read', 'plancours:write', 'plancours:list'])]
    private PlanCoursEnum $etatPlanCours = PlanCoursEnum::EN_COURS;

    #[ORM\Column]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?int $nbNotes = 2;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $cmPrevu = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $tdPrevu = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $tpPrevu = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $projetPrevu = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $cmRealise = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $tdRealise = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $tpRealise = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?float $projetRealise = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $bibliographie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $objectifs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $modalitesEvaluations = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $livrables = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $miseEnOeuvre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $modaliteEncadrementAutonomie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $saisiePlanCours = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $fichierPlanCours = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?bool $planSuivi = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $planSuiviCommentaire = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?bool $objectifsAtteints = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $objectifsAtteintsCommentaire = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?bool $competencesAcquises = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $competencesAcquisesCommentaire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $modeEvaluationCommentaire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $commentairesStep1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $commentairesStep2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $commentairesStep3 = null;

    /**
     * @var Collection<int, PlanCoursSequence>
     */
    #[ORM\OneToMany(targetEntity: PlanCoursSequence::class, mappedBy: 'planCours', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    #[Groups(['plancours:read', 'plancours:detail'])]
    private Collection $planCoursSequences;

    /**
     * @var Collection<int, PlanCoursRealise>
     */
    #[ORM\OneToMany(targetEntity: PlanCoursRealise::class, mappedBy: 'planCours', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['plancours:read', 'plancours:detail'])]
    private Collection $planCoursRealises;

    public function __construct()
    {
        $this->intervenants = new ArrayCollection();
        $this->planCoursSequences = new ArrayCollection();
        $this->planCoursRealises = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

        return $this;
    }

    public function getResponsable(): ?Personnel
    {
        return $this->responsable;
    }

    public function setResponsable(?Personnel $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * @return Collection<int, Personnel>
     */
    public function getIntervenants(): Collection
    {
        return $this->intervenants;
    }

    public function addIntervenant(Personnel $intervenant): static
    {
        if (!$this->intervenants->contains($intervenant)) {
            $this->intervenants->add($intervenant);
        }

        return $this;
    }

    public function removeIntervenant(Personnel $intervenant): static
    {
        $this->intervenants->removeElement($intervenant);

        return $this;
    }

    public function getEtatPlanCours(): PlanCoursEnum
    {
        return $this->etatPlanCours;
    }

    public function setEtatPlanCours(PlanCoursEnum|string $etatPlanCours): static
    {
        if (is_string($etatPlanCours)) {
            $this->etatPlanCours = PlanCoursEnum::from($etatPlanCours);
        } else {
            $this->etatPlanCours = $etatPlanCours;
        }

        return $this;
    }

    public function getNbNotes(): ?int
    {
        return $this->nbNotes;
    }

    public function setNbNotes(?int $nbNotes): static
    {
        $this->nbNotes = $nbNotes;

        return $this;
    }

    public function getCmPrevu(): ?float
    {
        return $this->cmPrevu;
    }

    public function setCmPrevu(?float $cmPrevu): static
    {
        $this->cmPrevu = $cmPrevu;

        return $this;
    }

    public function getTdPrevu(): ?float
    {
        return $this->tdPrevu;
    }

    public function setTdPrevu(?float $tdPrevu): static
    {
        $this->tdPrevu = $tdPrevu;

        return $this;
    }

    public function getTpPrevu(): ?float
    {
        return $this->tpPrevu;
    }

    public function setTpPrevu(?float $tpPrevu): static
    {
        $this->tpPrevu = $tpPrevu;

        return $this;
    }

    public function getProjetPrevu(): ?float
    {
        return $this->projetPrevu;
    }

    public function setProjetPrevu(?float $projetPrevu): static
    {
        $this->projetPrevu = $projetPrevu;

        return $this;
    }

    public function getCmRealise(): ?float
    {
        return $this->cmRealise;
    }

    public function setCmRealise(?float $cmRealise): static
    {
        $this->cmRealise = $cmRealise;

        return $this;
    }

    public function getTdRealise(): ?float
    {
        return $this->tdRealise;
    }

    public function setTdRealise(?float $tdRealise): static
    {
        $this->tdRealise = $tdRealise;

        return $this;
    }

    public function getTpRealise(): ?float
    {
        return $this->tpRealise;
    }

    public function setTpRealise(?float $tpRealise): static
    {
        $this->tpRealise = $tpRealise;

        return $this;
    }

    public function getProjetRealise(): ?float
    {
        return $this->projetRealise;
    }

    public function setProjetRealise(?float $projetRealise): static
    {
        $this->projetRealise = $projetRealise;

        return $this;
    }

    public function getBibliographie(): ?string
    {
        return $this->bibliographie;
    }

    public function setBibliographie(?string $bibliographie): static
    {
        $this->bibliographie = $bibliographie;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getObjectifs(): ?string
    {
        return $this->objectifs;
    }

    public function setObjectifs(?string $objectifs): static
    {
        $this->objectifs = $objectifs;

        return $this;
    }

    public function getModalitesEvaluations(): ?string
    {
        return $this->modalitesEvaluations;
    }

    public function setModalitesEvaluations(?string $modalitesEvaluations): static
    {
        $this->modalitesEvaluations = $modalitesEvaluations;

        return $this;
    }

    public function getLivrables(): ?string
    {
        return $this->livrables;
    }

    public function setLivrables(?string $livrables): static
    {
        $this->livrables = $livrables;

        return $this;
    }

    public function getMiseEnOeuvre(): ?string
    {
        return $this->miseEnOeuvre;
    }

    public function setMiseEnOeuvre(?string $miseEnOeuvre): static
    {
        $this->miseEnOeuvre = $miseEnOeuvre;

        return $this;
    }

    public function getModaliteEncadrementAutonomie(): ?string
    {
        return $this->modaliteEncadrementAutonomie;
    }

    public function setModaliteEncadrementAutonomie(?string $modaliteEncadrementAutonomie): static
    {
        $this->modaliteEncadrementAutonomie = $modaliteEncadrementAutonomie;

        return $this;
    }

    public function getSaisiePlanCours(): ?string
    {
        return $this->saisiePlanCours;
    }

    public function setSaisiePlanCours(?string $saisiePlanCours): static
    {
        $this->saisiePlanCours = $saisiePlanCours;

        return $this;
    }

    public function getFichierPlanCours(): ?string
    {
        return $this->fichierPlanCours;
    }

    public function setFichierPlanCours(?string $fichierPlanCours): static
    {
        $this->fichierPlanCours = $fichierPlanCours;

        return $this;
    }

    public function isPlanSuivi(): ?bool
    {
        return $this->planSuivi;
    }

    public function setPlanSuivi(?bool $planSuivi): static
    {
        $this->planSuivi = $planSuivi;

        return $this;
    }

    public function getPlanSuiviCommentaire(): ?string
    {
        return $this->planSuiviCommentaire;
    }

    public function setPlanSuiviCommentaire(?string $planSuiviCommentaire): static
    {
        $this->planSuiviCommentaire = $planSuiviCommentaire;

        return $this;
    }

    public function isObjectifsAtteints(): ?bool
    {
        return $this->objectifsAtteints;
    }

    public function setObjectifsAtteints(?bool $objectifsAtteints): static
    {
        $this->objectifsAtteints = $objectifsAtteints;

        return $this;
    }

    public function getObjectifsAtteintsCommentaire(): ?string
    {
        return $this->objectifsAtteintsCommentaire;
    }

    public function setObjectifsAtteintsCommentaire(?string $objectifsAtteintsCommentaire): static
    {
        $this->objectifsAtteintsCommentaire = $objectifsAtteintsCommentaire;

        return $this;
    }

    public function isCompetencesAcquises(): ?bool
    {
        return $this->competencesAcquises;
    }

    public function setCompetencesAcquises(?bool $competencesAcquises): static
    {
        $this->competencesAcquises = $competencesAcquises;

        return $this;
    }

    public function getCompetencesAcquisesCommentaire(): ?string
    {
        return $this->competencesAcquisesCommentaire;
    }

    public function setCompetencesAcquisesCommentaire(?string $competencesAcquisesCommentaire): static
    {
        $this->competencesAcquisesCommentaire = $competencesAcquisesCommentaire;

        return $this;
    }

    public function getModeEvaluationCommentaire(): ?string
    {
        return $this->modeEvaluationCommentaire;
    }

    public function setModeEvaluationCommentaire(?string $modeEvaluationCommentaire): static
    {
        $this->modeEvaluationCommentaire = $modeEvaluationCommentaire;

        return $this;
    }

    public function getCommentairesStep1(): ?string
    {
        return $this->commentairesStep1;
    }

    public function setCommentairesStep1(?string $commentairesStep1): static
    {
        $this->commentairesStep1 = $commentairesStep1;

        return $this;
    }

    public function getCommentairesStep2(): ?string
    {
        return $this->commentairesStep2;
    }

    public function setCommentairesStep2(?string $commentairesStep2): static
    {
        $this->commentairesStep2 = $commentairesStep2;

        return $this;
    }

    public function getCommentairesStep3(): ?string
    {
        return $this->commentairesStep3;
    }

    public function setCommentairesStep3(?string $commentairesStep3): static
    {
        $this->commentairesStep3 = $commentairesStep3;

        return $this;
    }

    /**
     * @return Collection<int, PlanCoursSequence>
     */
    public function getPlanCoursSequences(): Collection
    {
        return $this->planCoursSequences;
    }

    public function addPlanCoursSequence(PlanCoursSequence $planCoursSequence): static
    {
        if (!$this->planCoursSequences->contains($planCoursSequence)) {
            $this->planCoursSequences->add($planCoursSequence);
            $planCoursSequence->setPlanCours($this);
        }

        return $this;
    }

    public function removePlanCoursSequence(PlanCoursSequence $planCoursSequence): static
    {
        if ($this->planCoursSequences->removeElement($planCoursSequence)) {
            if ($planCoursSequence->getPlanCours() === $this) {
                $planCoursSequence->setPlanCours(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PlanCoursRealise>
     */
    public function getPlanCoursRealises(): Collection
    {
        return $this->planCoursRealises;
    }

    public function addPlanCoursRealise(PlanCoursRealise $planCoursRealise): static
    {
        if (!$this->planCoursRealises->contains($planCoursRealise)) {
            $this->planCoursRealises->add($planCoursRealise);
            $planCoursRealise->setPlanCours($this);
        }

        return $this;
    }

    public function removePlanCoursRealise(PlanCoursRealise $planCoursRealise): static
    {
        if ($this->planCoursRealises->removeElement($planCoursRealise)) {
            if ($planCoursRealise->getPlanCours() === $this) {
                $planCoursRealise->setPlanCours(null);
            }
        }

        return $this;
    }
}
