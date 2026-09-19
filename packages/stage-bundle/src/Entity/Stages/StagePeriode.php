<?php

namespace StageBundle\Entity\Stages;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Traits\OldIdTrait;
use StageBundle\Repository\Stages\StagePeriodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StagePeriodeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['stage_periode:read']]),
        new GetCollection(normalizationContext: ['groups' => ['stage_periode:read']]),
        new Post(securityPostDenormalize: "is_granted('ROLE_STAGE')"),
        new Patch(securityPostDenormalize: "is_granted('ROLE_STAGE')"),
        new Delete(security: "is_granted('ROLE_STAGE')"),
    ]
)]
class StagePeriode
{
    use OldIdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_periode:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['stage_periode:read'])]
    private ?string $libelle = null;

    #[ORM\ManyToOne]
    #[Groups(['stage_periode:read'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\ManyToOne]
    #[Groups(['stage_periode:read'])]
    private ?StructureSemestre $semestreProgramme = null;

    /** @var Collection<int, StructureSemestre> */
    #[ORM\ManyToMany(targetEntity: StructureSemestre::class)]
    #[Groups(['stage_periode:read'])]
    private Collection $semestresSaisie;

    #[ORM\Column]
    #[Groups(['stage_periode:read'])]
    private ?int $nbSemaines = null;

    #[ORM\Column]
    #[Groups(['stage_periode:read'])]
    private ?int $nbJours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['stage_periode:read'])]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['stage_periode:read'])]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Users\Personnel::class)]
    #[Groups(['stage_periode:read'])]
    private ?\App\Entity\Users\Personnel $responsablePrincipal = null;

    /** @var Collection<int, \App\Entity\Users\Personnel> */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Users\Personnel::class)]
    #[Groups(['stage_periode:read'])]
    private Collection $coResponsables;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['stage_periode:read'])]
    private bool $datesFlexibles = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)] #[Groups(['stage_periode:read'])] private ?string $description = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] #[Groups(['stage_periode:read'])] private ?string $commentaireLibre = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] #[Groups(['stage_periode:read'])] private ?string $competencesVisees = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] #[Groups(['stage_periode:read'])] private ?string $modalitesEvaluationEntreprise = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] #[Groups(['stage_periode:read'])] private ?string $modalitesEvaluationPedagogique = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] #[Groups(['stage_periode:read'])] private ?string $modalitesEncadrement = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] #[Groups(['stage_periode:read'])] private ?string $documentsRendre = null;
    #[ORM\Column(type: Types::JSON, nullable: true)] #[Groups(['stage_periode:read'])] private ?array $consignesFichiers = [];

    /** @var Collection<int, StagePeriodeInterruption> */
    #[ORM\OneToMany(targetEntity: StagePeriodeInterruption::class, mappedBy: 'stagePeriode', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['stage_periode:read'])]
    private Collection $periodesInterruption;

    /** @var Collection<int, StagePeriodeSoutenance> */
    #[ORM\OneToMany(targetEntity: StagePeriodeSoutenance::class, mappedBy: 'stagePeriode', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['stage_periode:read'])]
    private Collection $periodesSoutenance;

    /** @var Collection<int, StageEtudiant> */
    #[ORM\OneToMany(targetEntity: StageEtudiant::class, mappedBy: 'stagePeriode', orphanRemoval: true, cascade: ['remove'])]
    private Collection $stageEtudiants;

    public function __construct() { $this->semestresSaisie = new ArrayCollection(); $this->coResponsables = new ArrayCollection(); $this->periodesInterruption = new ArrayCollection(); $this->periodesSoutenance = new ArrayCollection(); $this->stageEtudiants = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getLibelle(): ?string { return $this->libelle; }
    public function setLibelle(string $v): static { $this->libelle = $v; return $this; }
    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire { return $this->anneeUniversitaire; }
    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $v): static { $this->anneeUniversitaire = $v; return $this; }
    public function getSemestreProgramme(): ?StructureSemestre { return $this->semestreProgramme; }
    public function setSemestreProgramme(?StructureSemestre $v): static { $this->semestreProgramme = $v; return $this; }
    public function getSemestresSaisie(): Collection { return $this->semestresSaisie; }
    public function addSemestresSaisie(StructureSemestre $v): static { if (!$this->semestresSaisie->contains($v)) { $this->semestresSaisie->add($v); } return $this; }
    public function removeSemestresSaisie(StructureSemestre $v): static { $this->semestresSaisie->removeElement($v); return $this; }
    public function getNbSemaines(): ?int { return $this->nbSemaines; }
    public function setNbSemaines(int $v): static { $this->nbSemaines = $v; return $this; }
    public function getNbJours(): ?int { return $this->nbJours; }
    public function setNbJours(int $v): static { $this->nbJours = $v; return $this; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $v): static { $this->dateDebut = $v; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $v): static { $this->dateFin = $v; return $this; }
    public function getResponsablePrincipal(): ?\App\Entity\Users\Personnel { return $this->responsablePrincipal; }
    public function setResponsablePrincipal(?\App\Entity\Users\Personnel $v): static { $this->responsablePrincipal = $v; return $this; }
    public function getCoResponsables(): Collection { return $this->coResponsables; }
    public function addCoResponsable(\App\Entity\Users\Personnel $v): static { if (!$this->coResponsables->contains($v)) { $this->coResponsables->add($v); } return $this; }
    public function removeCoResponsable(\App\Entity\Users\Personnel $v): static { $this->coResponsables->removeElement($v); return $this; }
    public function isDatesFlexibles(): bool { return $this->datesFlexibles; }
    public function setDatesFlexibles(bool $v): static { $this->datesFlexibles = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }
    public function getCommentaireLibre(): ?string { return $this->commentaireLibre; }
    public function setCommentaireLibre(?string $v): static { $this->commentaireLibre = $v; return $this; }
    public function getCompetencesVisees(): ?string { return $this->competencesVisees; }
    public function setCompetencesVisees(?string $v): static { $this->competencesVisees = $v; return $this; }
    public function getModalitesEvaluationEntreprise(): ?string { return $this->modalitesEvaluationEntreprise; }
    public function setModalitesEvaluationEntreprise(?string $v): static { $this->modalitesEvaluationEntreprise = $v; return $this; }
    public function getModalitesEvaluationPedagogique(): ?string { return $this->modalitesEvaluationPedagogique; }
    public function setModalitesEvaluationPedagogique(?string $v): static { $this->modalitesEvaluationPedagogique = $v; return $this; }
    public function getModalitesEncadrement(): ?string { return $this->modalitesEncadrement; }
    public function setModalitesEncadrement(?string $v): static { $this->modalitesEncadrement = $v; return $this; }
    public function getDocumentsRendre(): ?string { return $this->documentsRendre; }
    public function setDocumentsRendre(?string $v): static { $this->documentsRendre = $v; return $this; }
    public function getConsignesFichiers(): ?array { return $this->consignesFichiers; }
    public function setConsignesFichiers(?array $v): static { $this->consignesFichiers = $v; return $this; }
    public function getPeriodesInterruption(): Collection { return $this->periodesInterruption; }
    public function addPeriodeInterruption(StagePeriodeInterruption $v): static { if (!$this->periodesInterruption->contains($v)) { $this->periodesInterruption->add($v); $v->setStagePeriode($this); } return $this; }
    public function removePeriodeInterruption(StagePeriodeInterruption $v): static { if ($this->periodesInterruption->removeElement($v) && $v->getStagePeriode() === $this) { $v->setStagePeriode(null); } return $this; }
    public function getPeriodesSoutenance(): Collection { return $this->periodesSoutenance; }
    public function addPeriodeSoutenance(StagePeriodeSoutenance $v): static { if (!$this->periodesSoutenance->contains($v)) { $this->periodesSoutenance->add($v); $v->setStagePeriode($this); } return $this; }
    public function removePeriodeSoutenance(StagePeriodeSoutenance $v): static { if ($this->periodesSoutenance->removeElement($v) && $v->getStagePeriode() === $this) { $v->setStagePeriode(null); } return $this; }
    public function getStageEtudiants(): Collection { return $this->stageEtudiants; }
    public function addStageEtudiant(StageEtudiant $v): static { if (!$this->stageEtudiants->contains($v)) { $this->stageEtudiants->add($v); $v->setStagePeriode($this); } return $this; }
    public function removeStageEtudiant(StageEtudiant $v): static { if ($this->stageEtudiants->removeElement($v) && $v->getStagePeriode() === $this) { $v->setStagePeriode(null); } return $this; }
}
