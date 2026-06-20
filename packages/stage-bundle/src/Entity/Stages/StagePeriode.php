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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_periode:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['stage_periode:read'])]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'stagePeriodes')]
    #[Groups(['stage_periode:read'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\ManyToOne(inversedBy: 'stagePeriodes')]
    #[Groups(['stage_periode:read'])]
    private ?StructureSemestre $semestreProgramme = null;

    /**
     * @var Collection<int, StructureSemestre>
     */
    #[ORM\ManyToMany(targetEntity: StructureSemestre::class, inversedBy: 'stagePeriodes')]
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

    /**
     * @var Collection<int, \App\Entity\Users\Personnel>
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Users\Personnel::class)]
    #[Groups(['stage_periode:read'])]
    private Collection $coResponsables;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['stage_periode:read'])]
    private bool $datesFlexibles = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?string $commentaireLibre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?string $competencesVisees = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?string $modalitesEvaluationEntreprise = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?string $modalitesEvaluationPedagogique = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?string $modalitesEncadrement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?string $documentsRendre = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?array $consignesFichiers = [];

    /**
     * @var Collection<int, StagePeriodeInterruption>
     */
    #[ORM\OneToMany(targetEntity: StagePeriodeInterruption::class, mappedBy: 'stagePeriode', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['stage_periode:read'])]
    private Collection $periodesInterruption;

    /**
     * @var Collection<int, StagePeriodeSoutenance>
     */
    #[ORM\OneToMany(targetEntity: StagePeriodeSoutenance::class, mappedBy: 'stagePeriode', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['stage_periode:read'])]
    private Collection $periodesSoutenance;

    public function __construct()
    {
        $this->semestresSaisie = new ArrayCollection();
        $this->coResponsables = new ArrayCollection();
        $this->periodesInterruption = new ArrayCollection();
        $this->periodesSoutenance = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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

    public function getSemestreProgramme(): ?StructureSemestre
    {
        return $this->semestreProgramme;
    }

    public function setSemestreProgramme(?StructureSemestre $semestreProgramme): static
    {
        $this->semestreProgramme = $semestreProgramme;

        return $this;
    }

    /**
     * @return Collection<int, StructureSemestre>
     */
    public function getSemestresSaisie(): Collection
    {
        return $this->semestresSaisie;
    }

    public function addSemestresSaisie(StructureSemestre $semestresSaisie): static
    {
        if (!$this->semestresSaisie->contains($semestresSaisie)) {
            $this->semestresSaisie->add($semestresSaisie);
        }

        return $this;
    }

    public function removeSemestresSaisie(StructureSemestre $semestresSaisie): static
    {
        $this->semestresSaisie->removeElement($semestresSaisie);

        return $this;
    }

    public function getNbSemaines(): ?int
    {
        return $this->nbSemaines;
    }

    public function setNbSemaines(int $nbSemaines): static
    {
        $this->nbSemaines = $nbSemaines;

        return $this;
    }

    public function getNbJours(): ?int
    {
        return $this->nbJours;
    }

    public function setNbJours(int $nbJours): static
    {
        $this->nbJours = $nbJours;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getResponsablePrincipal(): ?\App\Entity\Users\Personnel
    {
        return $this->responsablePrincipal;
    }

    public function setResponsablePrincipal(?\App\Entity\Users\Personnel $responsablePrincipal): static
    {
        $this->responsablePrincipal = $responsablePrincipal;

        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\Users\Personnel>
     */
    public function getCoResponsables(): Collection
    {
        return $this->coResponsables;
    }

    public function addCoResponsable(\App\Entity\Users\Personnel $coResponsable): static
    {
        if (!$this->coResponsables->contains($coResponsable)) {
            $this->coResponsables->add($coResponsable);
        }

        return $this;
    }

    public function removeCoResponsable(\App\Entity\Users\Personnel $coResponsable): static
    {
        $this->coResponsables->removeElement($coResponsable);

        return $this;
    }

    public function isDatesFlexibles(): bool
    {
        return $this->datesFlexibles;
    }

    public function setDatesFlexibles(bool $datesFlexibles): static
    {
        $this->datesFlexibles = $datesFlexibles;

        return $this;
    }

    public function getCommentaireLibre(): ?string
    {
        return $this->commentaireLibre;
    }

    public function setCommentaireLibre(?string $commentaireLibre): static
    {
        $this->commentaireLibre = $commentaireLibre;

        return $this;
    }

    public function getCompetencesVisees(): ?string
    {
        return $this->competencesVisees;
    }

    public function setCompetencesVisees(?string $competencesVisees): static
    {
        $this->competencesVisees = $competencesVisees;

        return $this;
    }

    public function getModalitesEvaluationEntreprise(): ?string
    {
        return $this->modalitesEvaluationEntreprise;
    }

    public function setModalitesEvaluationEntreprise(?string $modalitesEvaluationEntreprise): static
    {
        $this->modalitesEvaluationEntreprise = $modalitesEvaluationEntreprise;

        return $this;
    }

    public function getModalitesEvaluationPedagogique(): ?string
    {
        return $this->modalitesEvaluationPedagogique;
    }

    public function setModalitesEvaluationPedagogique(?string $modalitesEvaluationPedagogique): static
    {
        $this->modalitesEvaluationPedagogique = $modalitesEvaluationPedagogique;

        return $this;
    }

    public function getModalitesEncadrement(): ?string
    {
        return $this->modalitesEncadrement;
    }

    public function setModalitesEncadrement(?string $modalitesEncadrement): static
    {
        $this->modalitesEncadrement = $modalitesEncadrement;

        return $this;
    }

    public function getDocumentsRendre(): ?string
    {
        return $this->documentsRendre;
    }

    public function setDocumentsRendre(?string $documentsRendre): static
    {
        $this->documentsRendre = $documentsRendre;

        return $this;
    }

    public function getConsignesFichiers(): ?array
    {
        return $this->consignesFichiers;
    }

    public function setConsignesFichiers(?array $consignesFichiers): static
    {
        $this->consignesFichiers = $consignesFichiers;

        return $this;
    }

    /**
     * @return Collection<int, StagePeriodeInterruption>
     */
    public function getPeriodesInterruption(): Collection
    {
        return $this->periodesInterruption;
    }

    public function addPeriodeInterruption(StagePeriodeInterruption $periodeInterruption): static
    {
        if (!$this->periodesInterruption->contains($periodeInterruption)) {
            $this->periodesInterruption->add($periodeInterruption);
            $periodeInterruption->setStagePeriode($this);
        }

        return $this;
    }

    public function removePeriodeInterruption(StagePeriodeInterruption $periodeInterruption): static
    {
        if ($this->periodesInterruption->removeElement($periodeInterruption)) {
            // set the owning side to null (unless already changed)
            if ($periodeInterruption->getStagePeriode() === $this) {
                $periodeInterruption->setStagePeriode(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StagePeriodeSoutenance>
     */
    public function getPeriodesSoutenance(): Collection
    {
        return $this->periodesSoutenance;
    }

    public function addPeriodeSoutenance(StagePeriodeSoutenance $periodeSoutenance): static
    {
        if (!$this->periodesSoutenance->contains($periodeSoutenance)) {
            $this->periodesSoutenance->add($periodeSoutenance);
            $periodeSoutenance->setStagePeriode($this);
        }

        return $this;
    }

    public function removePeriodeSoutenance(StagePeriodeSoutenance $periodeSoutenance): static
    {
        if ($this->periodesSoutenance->removeElement($periodeSoutenance)) {
            // set the owning side to null (unless already changed)
            if ($periodeSoutenance->getStagePeriode() === $this) {
                $periodeSoutenance->setStagePeriode(null);
            }
        }

        return $this;
    }
}
