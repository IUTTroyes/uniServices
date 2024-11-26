<?php

namespace App\Entity;

use App\Repository\StructurePnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructurePnRepository::class)]
class StructurePn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $annee_publication = null;

    #[ORM\ManyToOne(inversedBy: 'structurePns')]
    private ?StructureDiplome $diplome = null;

    #[ORM\ManyToOne(inversedBy: 'pn')]
    private ?StructureAnnee $structureAnnee = null;

    /**
     * @var Collection<int, StructureAnneeUniversitaire>
     */
    #[ORM\ManyToMany(targetEntity: StructureAnneeUniversitaire::class, mappedBy: 'pn')]
    private Collection $structureAnneeUniversitaires;

    public function __construct()
    {
        $this->structureAnneeUniversitaires = new ArrayCollection();
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

    public function getAnneePublication(): ?int
    {
        return $this->annee_publication;
    }

    public function setAnneePublication(int $annee_publication): static
    {
        $this->annee_publication = $annee_publication;

        return $this;
    }

    public function getDiplome(): ?StructureDiplome
    {
        return $this->diplome;
    }

    public function setDiplome(?StructureDiplome $diplome): static
    {
        $this->diplome = $diplome;

        return $this;
    }

    public function getStructureAnnee(): ?StructureAnnee
    {
        return $this->structureAnnee;
    }

    public function setStructureAnnee(?StructureAnnee $structureAnnee): static
    {
        $this->structureAnnee = $structureAnnee;

        return $this;
    }

    /**
     * @return Collection<int, StructureAnneeUniversitaire>
     */
    public function getStructureAnneeUniversitaires(): Collection
    {
        return $this->structureAnneeUniversitaires;
    }

    public function addStructureAnneeUniversitaire(StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        if (!$this->structureAnneeUniversitaires->contains($structureAnneeUniversitaire)) {
            $this->structureAnneeUniversitaires->add($structureAnneeUniversitaire);
            $structureAnneeUniversitaire->addPn($this);
        }

        return $this;
    }

    public function removeStructureAnneeUniversitaire(StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        if ($this->structureAnneeUniversitaires->removeElement($structureAnneeUniversitaire)) {
            $structureAnneeUniversitaire->removePn($this);
        }

        return $this;
    }
}
