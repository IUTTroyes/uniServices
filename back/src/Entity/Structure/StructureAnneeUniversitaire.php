<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Users\Personnel;
use App\Repository\StructureAnneeUniversitaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureAnneeUniversitaireRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['structure_annee_universitaire:read']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_annee_universitaire:read']]),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class StructureAnneeUniversitaire
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $annee = null;

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
    private ?bool $actif = null;

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

    public function __construct()
    {
        $this->scolarites = new ArrayCollection();
        $this->pn = new ArrayCollection();
        $this->personnels = new ArrayCollection();
        $this->apcReferentiels = new ArrayCollection();
        $this->scolEvaluations = new ArrayCollection();
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
}
