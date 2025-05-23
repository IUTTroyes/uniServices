<?php

namespace App\Entity\Apc;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureTypeDiplome;
use App\Repository\Apc\ApcReferentielRepository;
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
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'referentiel')]
    private Collection $diplomes;

    #[ORM\ManyToOne(inversedBy: 'referentiels')]
    private ?StructureDepartement $departement = null;

    /**
     * @var Collection<int, ApcCompetence>
     */
    #[ORM\OneToMany(targetEntity: ApcCompetence::class, mappedBy: 'referentiel')]
    private Collection $competences;

    #[ORM\ManyToOne(inversedBy: 'referentiels')]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    /**
     * @var Collection<int, ApcParcours>
     */
    #[ORM\OneToMany(targetEntity: ApcParcours::class, mappedBy: 'referentiel')]
    private Collection $parcours;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'referentiel')]
    private Collection $pn;

    #[ORM\ManyToOne(inversedBy: 'referentiels')]
    private StructureTypeDiplome $typeDiplome;

    public function __construct()
    {
        $this->diplomes = new ArrayCollection();
        $this->competences = new ArrayCollection();
        $this->parcours = new ArrayCollection();
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
            $diplome->setReferentiel($this);
        }

        return $this;
    }

    public function removeDiplome(StructureDiplome $diplome): static
    {
        if ($this->diplomes->removeElement($diplome)) {
            // set the owning side to null (unless already changed)
            if ($diplome->getReferentiel() === $this) {
                $diplome->setReferentiel(null);
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
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(ApcCompetence $competence): static
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
            $competence->setReferentiel($this);
        }

        return $this;
    }

    public function removeCompetence(ApcCompetence $competence): static
    {
        if ($this->competences->removeElement($competence)) {
            // set the owning side to null (unless already changed)
            if ($competence->getReferentiel() === $this) {
                $competence->setReferentiel(null);
            }
        }

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

    /**
     * @return Collection<int, ApcParcours>
     */
    public function getParcours(): Collection
    {
        return $this->parcours;
    }

    public function addParcours(ApcParcours $parcours): static
    {
        if (!$this->parcours->contains($parcours)) {
            $this->parcours->add($parcours);
            $parcours->setReferentiel($this);
        }

        return $this;
    }

    public function removeParcours(ApcParcours $parcours): static
    {
        if ($this->parcours->removeElement($parcours)) {
            // set the owning side to null (unless already changed)
            if ($parcours->getReferentiel() === $this) {
                $parcours->setReferentiel(null);
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
