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
use App\Entity\Previsionnel\Previsionnel;
use App\Entity\Scolarite\ScolEvaluation;
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
        new Get(normalizationContext: ['groups' => ['structure_annee_universitaire:read']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_annee_universitaire:read']]),
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
    #[Groups(['structure_annee_universitaire:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['structure_annee_universitaire:read'])]
    private ?string $libelle = null;

    #[ORM\Column]
    #[Groups(['structure_annee_universitaire:read'])]
    private int $annee;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'structureAnneeUniversitaire')]
    private Collection $scolarites;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\ManyToMany(targetEntity: StructurePn::class, inversedBy: 'structureAnneeUniversitaires')]
    private Collection $pn;

    /**
     * @var Collection<int, Personnel>
     */
    #[ORM\OneToMany(targetEntity: Personnel::class, mappedBy: 'structureAnneeUniversitaire')]
    private Collection $personnels;

    #[ORM\Column]
    #[Groups(['structure_annee_universitaire:read'])]
    private bool $actif = false;

    /**
     * @var Collection<int, ApcReferentiel>
     */
    #[ORM\OneToMany(targetEntity: ApcReferentiel::class, mappedBy: 'anneeUniv')]
    private Collection $apcReferentiels;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\OneToMany(targetEntity: ScolEvaluation::class, mappedBy: 'anneeUniv')]
    private Collection $scolEvaluations;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'anneeUniversitaire')]
    private Collection $scolEdtEvents;

    /**
     * @var Collection<int, StructureCalendrier>
     */
    #[ORM\OneToMany(targetEntity: StructureCalendrier::class, mappedBy: 'structureAnneeUniversitaire')]
    private Collection $structureCalendriers;

    /**
     * @var Collection<int, EdtCreneauxInterditsSemaine>
     */
    #[ORM\OneToMany(targetEntity: EdtCreneauxInterditsSemaine::class, mappedBy: 'anneeUniversitaire')]
    private Collection $edtCreneauxInterditsSemaines;

    /**
     * @var Collection<int, EdtContraintesSemestre>
     */
    #[ORM\OneToMany(targetEntity: EdtContraintesSemestre::class, mappedBy: 'anneeUniversitaire')]
    private Collection $edtContraintesSemestres;

    /**
     * @var Collection<int, Previsionnel>
     */
    #[ORM\OneToMany(targetEntity: Previsionnel::class, mappedBy: 'anneeUniversitaire')]
    private Collection $previsionnels;

    public function __construct()
    {
        $this->scolarites = new ArrayCollection();
        $this->pn = new ArrayCollection();
        $this->personnels = new ArrayCollection();
        $this->apcReferentiels = new ArrayCollection();
        $this->scolEvaluations = new ArrayCollection();
        $this->scolEdtEvents = new ArrayCollection();

        $this->annee = (int) date('Y');
        $this->structureCalendriers = new ArrayCollection();
        $this->edtCreneauxInterditsSemaines = new ArrayCollection();
        $this->edtContraintesSemestres = new ArrayCollection();
        $this->previsionnels = new ArrayCollection();
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
            $scolarite->setStructureAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeScolarite(EtudiantScolarite $scolarite): static
    {
        if ($this->scolarites->removeElement($scolarite)) {
            // set the owning side to null (unless already changed)
            if ($scolarite->getStructureAnneeUniversitaire() === $this) {
                $scolarite->setStructureAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructurePn>
     */
    public function getPn(): Collection
    {
        return $this->pn;
    }

    public function addPn(StructurePn $pn): static
    {
        if (!$this->pn->contains($pn)) {
            $this->pn->add($pn);
        }

        return $this;
    }

    public function removePn(StructurePn $pn): static
    {
        $this->pn->removeElement($pn);

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
            $personnel->setStructureAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removePersonnel(Personnel $personnel): static
    {
        if ($this->personnels->removeElement($personnel)) {
            // set the owning side to null (unless already changed)
            if ($personnel->getStructureAnneeUniversitaire() === $this) {
                $personnel->setStructureAnneeUniversitaire(null);
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
    public function getApcReferentiels(): Collection
    {
        return $this->apcReferentiels;
    }

    public function addApcReferentiel(ApcReferentiel $apcReferentiel): static
    {
        if (!$this->apcReferentiels->contains($apcReferentiel)) {
            $this->apcReferentiels->add($apcReferentiel);
            $apcReferentiel->setAnneeUniv($this);
        }

        return $this;
    }

    public function removeApcReferentiel(ApcReferentiel $apcReferentiel): static
    {
        if ($this->apcReferentiels->removeElement($apcReferentiel)) {
            // set the owning side to null (unless already changed)
            if ($apcReferentiel->getAnneeUniv() === $this) {
                $apcReferentiel->setAnneeUniv(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScolEvaluation>
     */
    public function getScolEvaluations(): Collection
    {
        return $this->scolEvaluations;
    }

    public function addScolEvaluation(ScolEvaluation $scolEvaluation): static
    {
        if (!$this->scolEvaluations->contains($scolEvaluation)) {
            $this->scolEvaluations->add($scolEvaluation);
            $scolEvaluation->setAnneeUniv($this);
        }

        return $this;
    }

    public function removeScolEvaluation(ScolEvaluation $scolEvaluation): static
    {
        if ($this->scolEvaluations->removeElement($scolEvaluation)) {
            // set the owning side to null (unless already changed)
            if ($scolEvaluation->getAnneeUniv() === $this) {
                $scolEvaluation->setAnneeUniv(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtEvent>
     */
    public function getScolEdtEvents(): Collection
    {
        return $this->scolEdtEvents;
    }

    public function addScolEdtEvent(EdtEvent $scolEdtEvent): static
    {
        if (!$this->scolEdtEvents->contains($scolEdtEvent)) {
            $this->scolEdtEvents->add($scolEdtEvent);
            $scolEdtEvent->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeScolEdtEvent(EdtEvent $scolEdtEvent): static
    {
        if ($this->scolEdtEvents->removeElement($scolEdtEvent)) {
            // set the owning side to null (unless already changed)
            if ($scolEdtEvent->getAnneeUniversitaire() === $this) {
                $scolEdtEvent->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructureCalendrier>
     */
    public function getStructureCalendriers(): Collection
    {
        return $this->structureCalendriers;
    }

    public function addStructureCalendrier(StructureCalendrier $structureCalendrier): static
    {
        if (!$this->structureCalendriers->contains($structureCalendrier)) {
            $this->structureCalendriers->add($structureCalendrier);
            $structureCalendrier->setStructureAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeStructureCalendrier(StructureCalendrier $structureCalendrier): static
    {
        if ($this->structureCalendriers->removeElement($structureCalendrier)) {
            // set the owning side to null (unless already changed)
            if ($structureCalendrier->getStructureAnneeUniversitaire() === $this) {
                $structureCalendrier->setStructureAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtCreneauxInterditsSemaine>
     */
    public function getEdtCreneauxInterditsSemaines(): Collection
    {
        return $this->edtCreneauxInterditsSemaines;
    }

    public function addEdtCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $edtCreneauxInterditsSemaine): static
    {
        if (!$this->edtCreneauxInterditsSemaines->contains($edtCreneauxInterditsSemaine)) {
            $this->edtCreneauxInterditsSemaines->add($edtCreneauxInterditsSemaine);
            $edtCreneauxInterditsSemaine->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeEdtCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $edtCreneauxInterditsSemaine): static
    {
        if ($this->edtCreneauxInterditsSemaines->removeElement($edtCreneauxInterditsSemaine)) {
            // set the owning side to null (unless already changed)
            if ($edtCreneauxInterditsSemaine->getAnneeUniversitaire() === $this) {
                $edtCreneauxInterditsSemaine->setAnneeUniversitaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtContraintesSemestre>
     */
    public function getEdtContraintesSemestres(): Collection
    {
        return $this->edtContraintesSemestres;
    }

    public function addEdtContraintesSemestre(EdtContraintesSemestre $edtContraintesSemestre): static
    {
        if (!$this->edtContraintesSemestres->contains($edtContraintesSemestre)) {
            $this->edtContraintesSemestres->add($edtContraintesSemestre);
            $edtContraintesSemestre->setAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeEdtContraintesSemestre(EdtContraintesSemestre $edtContraintesSemestre): static
    {
        if ($this->edtContraintesSemestres->removeElement($edtContraintesSemestre)) {
            // set the owning side to null (unless already changed)
            if ($edtContraintesSemestre->getAnneeUniversitaire() === $this) {
                $edtContraintesSemestre->setAnneeUniversitaire(null);
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
}
