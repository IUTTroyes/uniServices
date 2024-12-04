<?php

namespace App\Entity\Apc;

use App\Entity\Structure\StructureAnnee;
use App\Repository\ApcNiveauRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApcNiveauRepository::class)]
class ApcNiveau
{
    final public const NIVEAU_1 = 'Novice';
    final public const NIVEAU_2 = 'Intermédiaire';
    final public const NIVEAU_3 = 'Compétent';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\ManyToOne(inversedBy: 'apcNiveaux')]
    private ?StructureAnnee $annee = null;

    /**
     * @var Collection<int, ApcParcours>
     */
    #[ORM\ManyToMany(targetEntity: ApcParcours::class, inversedBy: 'apcNiveaux')]
    private Collection $apcParcours;

    #[ORM\ManyToOne(inversedBy: 'niveau')]
    private ?ApcCompetence $apcCompetence = null;

    /**
     * @var Collection<int, ApcApprentissageCritique>
     */
    #[ORM\OneToMany(targetEntity: ApcApprentissageCritique::class, mappedBy: 'apcNiveau')]
    private Collection $apcApprentissageCritique;

    public function __construct()
    {
        $this->apcParcours = new ArrayCollection();
        $this->apcApprentissageCritique = new ArrayCollection();
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

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getAnnee(): ?StructureAnnee
    {
        return $this->annee;
    }

    public function setAnnee(?StructureAnnee $annee): static
    {
        $this->annee = $annee;

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
        }

        return $this;
    }

    public function removeApcParcour(ApcParcours $apcParcour): static
    {
        $this->apcParcours->removeElement($apcParcour);

        return $this;
    }

    public function getApcCompetence(): ?ApcCompetence
    {
        return $this->apcCompetence;
    }

    public function setApcCompetence(?ApcCompetence $apcCompetence): static
    {
        $this->apcCompetence = $apcCompetence;

        return $this;
    }

    /**
     * @return Collection<int, ApcApprentissageCritique>
     */
    public function getApcApprentissageCritique(): Collection
    {
        return $this->apcApprentissageCritique;
    }

    public function addApcApprentissageCritique(ApcApprentissageCritique $apcApprentissageCritique): static
    {
        if (!$this->apcApprentissageCritique->contains($apcApprentissageCritique)) {
            $this->apcApprentissageCritique->add($apcApprentissageCritique);
            $apcApprentissageCritique->setApcNiveau($this);
        }

        return $this;
    }

    public function removeApcApprentissageCritique(ApcApprentissageCritique $apcApprentissageCritique): static
    {
        if ($this->apcApprentissageCritique->removeElement($apcApprentissageCritique)) {
            // set the owning side to null (unless already changed)
            if ($apcApprentissageCritique->getApcNiveau() === $this) {
                $apcApprentissageCritique->setApcNiveau(null);
            }
        }

        return $this;
    }
}