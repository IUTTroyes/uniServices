<?php

namespace App\Entity\Structure;

use App\Repository\StructureAnneeUniversitaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureAnneeUniversitaireRepository::class)]
class StructureAnneeUniversitaire
{
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
     * @var Collection<int, StructureScolarite>
     */
    #[ORM\OneToMany(targetEntity: StructureScolarite::class, mappedBy: 'structureAnneeUniversitaire')]
    private Collection $scolarit�es;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\ManyToMany(targetEntity: StructurePn::class, inversedBy: 'structureAnneeUniversitaires')]
    private Collection $pn;

    /**
     * @var Collection<int, StructurePersonnel>
     */
    #[ORM\OneToMany(targetEntity: StructurePersonnel::class, mappedBy: 'structureAnneeUniversitaire')]
    private Collection $personnels;

    #[ORM\Column]
    private ?bool $actif = null;

    public function __construct()
    {
        $this->scolarit�es = new ArrayCollection();
        $this->pn = new ArrayCollection();
        $this->personnels = new ArrayCollection();
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
     * @return Collection<int, StructureScolarite>
     */
    public function getScolarit�es(): Collection
    {
        return $this->scolarit�es;
    }

    public function addScolaritE(StructureScolarite $scolaritE): static
    {
        if (!$this->scolarit�es->contains($scolaritE)) {
            $this->scolarit�es->add($scolaritE);
            $scolaritE->setStructureAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removeScolaritE(StructureScolarite $scolaritE): static
    {
        if ($this->scolarit�es->removeElement($scolaritE)) {
            // set the owning side to null (unless already changed)
            if ($scolaritE->getStructureAnneeUniversitaire() === $this) {
                $scolaritE->setStructureAnneeUniversitaire(null);
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
     * @return Collection<int, StructurePersonnel>
     */
    public function getPersonnels(): Collection
    {
        return $this->personnels;
    }

    public function addPersonnel(StructurePersonnel $personnel): static
    {
        if (!$this->personnels->contains($personnel)) {
            $this->personnels->add($personnel);
            $personnel->setStructureAnneeUniversitaire($this);
        }

        return $this;
    }

    public function removePersonnel(StructurePersonnel $personnel): static
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
}
