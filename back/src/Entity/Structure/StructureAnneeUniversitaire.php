<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Edt\EdtContraintesSemestre;
use App\Entity\Edt\EdtCreneauxInterditsSemaine;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Personnel\PersonnelEnseignantHrs;
use App\Entity\Previsionnel\Previsionnel;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Stages\StagePeriode;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Users\Personnel;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureAnneeUniversitaireRepository::class)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['annee_universitaire:read']]),
        new GetCollection(normalizationContext: ['groups' => ['annee_universitaire:read']]),
        new Post(),
        new Patch(),
        new Delete()
    ]
)]
#[ORM\HasLifecycleCallbacks]
class StructureAnneeUniversitaire
{
    use LifeCycleTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['annee_universitaire:read', 'etudiant:read', 'maquette:detail', 'annee-univ:light'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['annee_universitaire:read', 'scolarite:read', 'annee-univ:light'])]
    private ?string $libelle = null;

    #[ORM\Column]
    #[Groups(['annee_universitaire:read', 'scolarite:read'])]
    private int $annee;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'anneeUniversitaire')]
    private Collection $scolarites;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'anneeUniversitaire')]
    private Collection $pns;

    /**
     * @var Collection<int, Personnel>
     */
    #[ORM\OneToMany(targetEntity: Personnel::class, mappedBy: 'anneeUniversitaire')]
    private Collection $personnels;

    #[ORM\Column]
    #[Groups(['annee_universitaire:read', 'maquette:detail', 'pn:read', 'scolarite:read', 'etudiant:read'])]
    private bool $actif = false;

    /**
     * @var Collection<int, ApcReferentiel>
     */
    #[ORM\OneToMany(targetEntity: ApcReferentiel::class, mappedBy: 'anneeUniversitaire')]
    private Collection $referentiels;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\OneToMany(targetEntity: ScolEvaluation::class, mappedBy: 'anneeUniversitaire')]
    private Collection $evaluations;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'anneeUniversitaire')]
    private Collection $edtEvents;

    /**
     * @var Collection<int, StructureCalendrier>
     */
    #[ORM\OneToMany(targetEntity: StructureCalendrier::class, mappedBy: 'anneeUniversitaire')]
    private Collection $calendriers;

    /**
     * @var Collection<int, EdtCreneauxInterditsSemaine>
     */
    #[ORM\OneToMany(targetEntity: EdtCreneauxInterditsSemaine::class, mappedBy: 'anneeUniversitaire')]
    private Collection $creneauxInterditsSemaines;

    /**
     * @var Collection<int, EdtContraintesSemestre>
     */
    #[ORM\OneToMany(targetEntity: EdtContraintesSemestre::class, mappedBy: 'anneeUniversitaire')]
    private Collection $contraintesSemestres;

    /**
     * @var Collection<int, Previsionnel>
     */
    #[ORM\OneToMany(targetEntity: Previsionnel::class, mappedBy: 'anneeUniversitaire')]
    private Collection $previsionnels;

    /**
     * @var Collection<int, StagePeriode>
     */
    #[ORM\OneToMany(targetEntity: StagePeriode::class, mappedBy: 'anneeUniversitaire')]
    private Collection $stagePeriodes;

    /**
     * @var Collection<int, PersonnelEnseignantHrs>
     */
    #[ORM\OneToMany(targetEntity: PersonnelEnseignantHrs::class, mappedBy: 'annee_universitaire')]
    private Collection $enseignantHrs;

    public function __construct()
    {
        $this->scolarites = new ArrayCollection();
        $this->pns = new ArrayCollection();
        $this->personnels = new ArrayCollection();
        $this->referentiels = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->edtEvents = new ArrayCollection();

        $this->annee = (int) date('Y');
        $this->calendriers = new ArrayCollection();
        $this->creneauxInterditsSemaines = new ArrayCollection();
        $this->contraintesSemestres = new ArrayCollection();
        $this->previsionnels = new ArrayCollection();
        $this->stagePeriodes = new ArrayCollection();
        $this->enseignantHrs = new ArrayCollection();
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

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantScolarite>
     */
    public function getScolarites(): Collection
    {
        return $this->scolarites;
    }

    public function addScolarite(EtudiantScolarite $scolarite): static
    {
        if (!$this->scolarites->contains($scolarite)) {
            $this->scolarites->add($scolarite);
            $scolarite->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeScolarite(EtudiantScolarite $scolarite): static
    {
        if ($this->scolarites->removeElement($scolarite)) {
            // set the owning side to null (unless already changed)
            if ($scolarite->getAnneeUniversitaire() === $this) {
                $scolarite->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructurePn>
     */
    public function getPns(): Collection
    {
        return $this->pns;
    }

    public function addPn(StructurePn $pn): static
    {
        if (!$this->pns->contains($pn)) {
            $this->pns->add($pn);
            $pn->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removePn(StructurePn $pn): static
    {
        if ($this->pns->removeElement($pn)) {
            // set the owning side to null (unless already changed)
            if ($pn->getAnneeUniversitaire() === $this) {
                $pn->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Personnel>
     */
    public function getPersonnels(): Collection
    {
        return $this->personnels;
    }

    public function addPersonnel(Personnel $personnel): static
    {
        if (!$this->personnels->contains($personnel)) {
            $this->personnels->add($personnel);
            $personnel->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removePersonnel(Personnel $personnel): static
    {
        if ($this->personnels->removeElement($personnel)) {
            // set the owning side to null (unless already changed)
            if ($personnel->getAnneeUniversitaire() === $this) {
                $personnel->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, ApcReferentiel>
     */
    public function getReferentiels(): Collection
    {
        return $this->referentiels;
    }

    public function addReferentiel(ApcReferentiel $referentiel): static
    {
        if (!$this->referentiels->contains($referentiel)) {
            $this->referentiels->add($referentiel);
            $referentiel->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeReferentiel(ApcReferentiel $referentiel): static
    {
        if ($this->referentiels->removeElement($referentiel)) {
            // set the owning side to null (unless already changed)
            if ($referentiel->getAnneeUniversitaire() === $this) {
                $referentiel->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScolEvaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(ScolEvaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeEvaluation(ScolEvaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getAnneeUniversitaire() === $this) {
                $evaluation->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtEvent>
     */
    public function getEdtEvents(): Collection
    {
        return $this->edtEvents;
    }

    public function addEdtEvent(EdtEvent $edtEvent): static
    {
        if (!$this->edtEvents->contains($edtEvent)) {
            $this->edtEvents->add($edtEvent);
            $edtEvent->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeEdtEvent(EdtEvent $edtEvent): static
    {
        if ($this->edtEvents->removeElement($edtEvent)) {
            // set the owning side to null (unless already changed)
            if ($edtEvent->getAnneeUniversitaire() === $this) {
                $edtEvent->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructureCalendrier>
     */
    public function getCalendriers(): Collection
    {
        return $this->calendriers;
    }

    public function addCalendrier(StructureCalendrier $calendrier): static
    {
        if (!$this->calendriers->contains($calendrier)) {
            $this->calendriers->add($calendrier);
            $calendrier->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeCalendrier(StructureCalendrier $calendrier): static
    {
        if ($this->calendriers->removeElement($calendrier)) {
            // set the owning side to null (unless already changed)
            if ($calendrier->getAnneeUniversitaire() === $this) {
                $calendrier->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtCreneauxInterditsSemaine>
     */
    public function getCreneauxInterditsSemaines(): Collection
    {
        return $this->creneauxInterditsSemaines;
    }

    public function addCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $creneauxInterditsSemaine): static
    {
        if (!$this->creneauxInterditsSemaines->contains($creneauxInterditsSemaine)) {
            $this->creneauxInterditsSemaines->add($creneauxInterditsSemaine);
            $creneauxInterditsSemaine->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $creneauxInterditsSemaine): static
    {
        if ($this->creneauxInterditsSemaines->removeElement($creneauxInterditsSemaine)) {
            // set the owning side to null (unless already changed)
            if ($creneauxInterditsSemaine->getAnneeUniversitaire() === $this) {
                $creneauxInterditsSemaine->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtContraintesSemestre>
     */
    public function getContraintesSemestres(): Collection
    {
        return $this->contraintesSemestres;
    }

    public function addContraintesSemestre(EdtContraintesSemestre $contraintesSemestre): static
    {
        if (!$this->contraintesSemestres->contains($contraintesSemestre)) {
            $this->contraintesSemestres->add($contraintesSemestre);
            $contraintesSemestre->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeContraintesSemestre(EdtContraintesSemestre $contraintesSemestre): static
    {
        if ($this->contraintesSemestres->removeElement($contraintesSemestre)) {
            // set the owning side to null (unless already changed)
            if ($contraintesSemestre->getAnneeUniversitaire() === $this) {
                $contraintesSemestre->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Previsionnel>
     */
    public function getPrevisionnels(): Collection
    {
        return $this->previsionnels;
    }

    public function addPrevisionnel(Previsionnel $previsionnel): static
    {
        if (!$this->previsionnels->contains($previsionnel)) {
            $this->previsionnels->add($previsionnel);
            $previsionnel->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removePrevisionnel(Previsionnel $previsionnel): static
    {
        if ($this->previsionnels->removeElement($previsionnel)) {
            // set the owning side to null (unless already changed)
            if ($previsionnel->getAnneeUniversitaire() === $this) {
                $previsionnel->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StagePeriode>
     */
    public function getStagePeriodes(): Collection
    {
        return $this->stagePeriodes;
    }

    public function addStagePeriode(StagePeriode $stagePeriode): static
    {
        if (!$this->stagePeriodes->contains($stagePeriode)) {
            $this->stagePeriodes->add($stagePeriode);
            $stagePeriode->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeStagePeriode(StagePeriode $stagePeriode): static
    {
        if ($this->stagePeriodes->removeElement($stagePeriode)) {
            // set the owning side to null (unless already changed)
            if ($stagePeriode->getAnneeUniversitaire() === $this) {
                $stagePeriode->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PersonnelEnseignantHrs>
     */
    public function getEnseignantHrs(): Collection
    {
        return $this->enseignantHrs;
    }

    public function addEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if (!$this->enseignantHrs->contains($enseignantHr)) {
            $this->enseignantHrs->add($enseignantHr);
            $enseignantHr->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if ($this->enseignantHrs->removeElement($enseignantHr)) {
            // set the owning side to null (unless already changed)
            if ($enseignantHr->getAnneeUniversitaire() === $this) {
                $enseignantHr->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }
}
