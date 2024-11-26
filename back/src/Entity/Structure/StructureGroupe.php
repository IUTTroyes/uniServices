<?php

namespace App\Entity\Structure;

use App\Repository\StructureGroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureGroupeRepository::class)]
class StructureGroupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50)]
    private ?string $code_apogee = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $parent = null;

    /**
     * @var Collection<int, StructureEtudiant>
     */
    #[ORM\ManyToMany(targetEntity: StructureEtudiant::class, inversedBy: 'structureGroupes')]
    private Collection $etudiants;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    private ?self $enfants = null;

    #[ORM\Column(nullable: true)]
    private ?int $ordre = null;

    /**
     * @var Collection<int, StructureSemestre>
     */
    #[ORM\ManyToMany(targetEntity: StructureSemestre::class, inversedBy: 'structureGroupes')]
    private Collection $semestres;

    public function __construct()
    {
        $this->etudiants = new ArrayCollection();
        $this->enfants = new ArrayCollection();
        $this->semestres = new ArrayCollection();
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

    public function getCodeApogee(): ?string
    {
        return $this->code_apogee;
    }

    public function setCodeApogee(string $code_apogee): static
    {
        $this->code_apogee = $code_apogee;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, StructureEtudiant>
     */
    public function getEtudiants(): Collection
    {
        return $this->etudiants;
    }

    public function addEtudiant(StructureEtudiant $etudiant): static
    {
        if (!$this->etudiants->contains($etudiant)) {
            $this->etudiants->add($etudiant);
        }

        return $this;
    }

    public function removeEtudiant(StructureEtudiant $etudiant): static
    {
        $this->etudiants->removeElement($etudiant);

        return $this;
    }

    public function getEnfants(): ?self
    {
        return $this->enfants;
    }

    public function setEnfants(?self $enfants): static
    {
        $this->enfants = $enfants;

        return $this;
    }

    public function addEnfant(self $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setEnfants($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getEnfants() === $this) {
                $enfant->setEnfants(null);
            }
        }

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * @return Collection<int, StructureSemestre>
     */
    public function getSemestres(): Collection
    {
        return $this->semestres;
    }

    public function addSemestre(StructureSemestre $semestre): static
    {
        if (!$this->semestres->contains($semestre)) {
            $this->semestres->add($semestre);
        }

        return $this;
    }

    public function removeSemestre(StructureSemestre $semestre): static
    {
        $this->semestres->removeElement($semestre);

        return $this;
    }
}
