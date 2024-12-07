<?php

namespace App\Entity\Apc;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureTypeDiplome;
use App\Repository\ApcReferentielRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApcReferentielRepository::class)]
#[ApiResource]
class ApcReferentiel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneePublication = null;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'apcReferentiel')]
    private Collection $diplomes;

    #[ORM\ManyToOne(inversedBy: 'apcReferentiels')]
    private ?StructureDepartement $departement = null;

    /**
     * @var Collection<int, ApcCompetence>
     */
    #[ORM\OneToMany(targetEntity: ApcCompetence::class, mappedBy: 'referentiel')]
    private Collection $apcCompetences;

    #[ORM\ManyToOne(inversedBy: 'apcReferentiels')]
    private ?StructureAnneeUniversitaire $anneeUniv = null;

    /**
     * @var Collection<int, ApcParcours>
     */
    #[ORM\OneToMany(targetEntity: ApcParcours::class, mappedBy: 'apcReferentiel')]
    private Collection $apcParcours;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'apcReferentiel')]
    private Collection $pn;

    #[ORM\ManyToOne(inversedBy: 'apcReferentiels')]
    private StructureTypeDiplome $typeDiplome;

    public function __construct()
    {
        $this->diplomes = new ArrayCollection();
        $this->apcCompetences = new ArrayCollection();
        $this->apcParcours = new ArrayCollection();
        $this->pn = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAnneePublication(): ?int
    {
        return $this->anneePublication;
    }

    public function setAnneePublication(?int $anneePublication): static
    {
        $this->anneePublication = $anneePublication;

        return $this;
    }

    /**
     * @return Collection<int, StructureDiplome>
     */
    public function getDiplomes(): Collection
    {
        return $this->diplomes;
    }

    public function addDiplome(StructureDiplome $diplome): static
    {
        if (!$this->diplomes->contains($diplome)) {
            $this->diplomes->add($diplome);
            $diplome->setApcReferentiel($this);
        }

        return $this;
    }

    public function removeDiplome(StructureDiplome $diplome): static
    {
        if ($this->diplomes->removeElement($diplome)) {
            // set the owning side to null (unless already changed)
            if ($diplome->getApcReferentiel() === $this) {
                $diplome->setApcReferentiel(null);
            }
        }

        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    /**
     * @return Collection<int, ApcCompetence>
     */
    public function getApcCompetences(): Collection
    {
        return $this->apcCompetences;
    }

    public function addApcCompetence(ApcCompetence $apcCompetence): static
    {
        if (!$this->apcCompetences->contains($apcCompetence)) {
            $this->apcCompetences->add($apcCompetence);
            $apcCompetence->setApcReferentiel($this);
        }

        return $this;
    }

    public function removeApcCompetence(ApcCompetence $apcCompetence): static
    {
        if ($this->apcCompetences->removeElement($apcCompetence)) {
            // set the owning side to null (unless already changed)
            if ($apcCompetence->getApcReferentiel() === $this) {
                $apcCompetence->setApcReferentiel(null);
            }
        }

        return $this;
    }

    public function getAnneeUniv(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniv;
    }

    public function setAnneeUniv(?StructureAnneeUniversitaire $anneeUniv): static
    {
        $this->anneeUniv = $anneeUniv;

        return $this;
    }

    /**
     * @return Collection<int, ApcParcours>
     */
    public function getApcParcours(): Collection
    {
        return $this->apcParcours;
    }

    public function addApcParcour(ApcParcours $apcParcour): static
    {
        if (!$this->apcParcours->contains($apcParcour)) {
            $this->apcParcours->add($apcParcour);
            $apcParcour->setApcReferentiel($this);
        }

        return $this;
    }

    public function removeApcParcour(ApcParcours $apcParcour): static
    {
        if ($this->apcParcours->removeElement($apcParcour)) {
            // set the owning side to null (unless already changed)
            if ($apcParcour->getApcReferentiel() === $this) {
                $apcParcour->setApcReferentiel(null);
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
            $pn->setApcReferentiel($this);
        }

        return $this;
    }

    public function removePn(StructurePn $pn): static
    {
        if ($this->pn->removeElement($pn)) {
            // set the owning side to null (unless already changed)
            if ($pn->getApcReferentiel() === $this) {
                $pn->setApcReferentiel(null);
            }
        }

        return $this;
    }

    public function getTypeDiplome(): StructureTypeDiplome
    {
        return $this->typeDiplome;
    }

    public function setTypeDiplome(StructureTypeDiplome $typeDiplome): static
    {
        $this->typeDiplome = $typeDiplome;

        return $this;
    }
}
