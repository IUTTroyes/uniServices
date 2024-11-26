<?php

namespace App\Entity;

use App\Repository\StructureAnneeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureAnneeRepository::class)]
class StructureAnnee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $code_etape = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $code_version = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle_long = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $couleur = null;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'structureAnnee')]
    private Collection $pn;

    #[ORM\Column]
    private array $opt = [];

    public function __construct()
    {
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

    public function getCodeEtape(): ?string
    {
        return $this->code_etape;
    }

    public function setCodeEtape(?string $code_etape): static
    {
        $this->code_etape = $code_etape;

        return $this;
    }

    public function getCodeVersion(): ?string
    {
        return $this->code_version;
    }

    public function setCodeVersion(?string $code_version): static
    {
        $this->code_version = $code_version;

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

    public function getLibelleLong(): ?string
    {
        return $this->libelle_long;
    }

    public function setLibelleLong(?string $libelle_long): static
    {
        $this->libelle_long = $libelle_long;

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

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;

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
            $pn->setStructureAnnee($this);
        }

        return $this;
    }

    public function removePn(StructurePn $pn): static
    {
        if ($this->pn->removeElement($pn)) {
            // set the owning side to null (unless already changed)
            if ($pn->getStructureAnnee() === $this) {
                $pn->setStructureAnnee(null);
            }
        }

        return $this;
    }

    public function getOpt(): array
    {
        return $this->opt;
    }

    public function setOpt(array $opt): static
    {
        $this->opt = $opt;

        return $this;
    }
}
