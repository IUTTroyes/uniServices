<?php

namespace App\Entity\Structure;

use App\Repository\StructureDiplomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureDiplomeRepository::class)]
class StructureDiplome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'responsableDiplome')]
    private ?StructurePersonnel $responsable_diplome = null;

    #[ORM\ManyToOne(inversedBy: 'assistant_diplome')]
    private ?StructurePersonnel $assistant_diplome = null;

    #[ORM\Column]
    private ?int $volume_horaire = null;

    #[ORM\Column]
    private ?int $code_celcat_departement = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $sigle = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    private ?Collection $enfants = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo_partenaire = null;

    #[ORM\Column]
    private array $opt = [];

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'diplome')]
    private Collection $structurePns;

    #[ORM\ManyToOne(inversedBy: 'structureDiplomes')]
    private ?StructureDepartement $departement = null;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
        $this->structurePns = new ArrayCollection();
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

    public function getResponsableDiplome(): ?StructurePersonnel
    {
        return $this->responsable_diplome;
    }

    public function setResponsableDiplome(?StructurePersonnel $responsable_diplome): static
    {
        $this->responsable_diplome = $responsable_diplome;

        return $this;
    }

    public function getAssistantDiplome(): ?StructurePersonnel
    {
        return $this->assistant_diplome;
    }

    public function setAssistantDiplome(?StructurePersonnel $assistant_diplome): static
    {
        $this->assistant_diplome = $assistant_diplome;

        return $this;
    }

    public function getVolumeHoraire(): ?int
    {
        return $this->volume_horaire;
    }

    public function setVolumeHoraire(int $volume_horaire): static
    {
        $this->volume_horaire = $volume_horaire;

        return $this;
    }

    public function getCodeCelcatDepartement(): ?int
    {
        return $this->code_celcat_departement;
    }

    public function setCodeCelcatDepartement(int $code_celcat_departement): static
    {
        $this->code_celcat_departement = $code_celcat_departement;

        return $this;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): static
    {
        $this->sigle = $sigle;

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
     * @return Collection<int, self>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(?self $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }

        return $this;
    }

    public function getLogoPartenaire(): ?string
    {
        return $this->logo_partenaire;
    }

    public function setLogoPartenaire(?string $logo_partenaire): static
    {
        $this->logo_partenaire = $logo_partenaire;

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

    /**
     * @return Collection<int, StructurePn>
     */
    public function getStructurePns(): Collection
    {
        return $this->structurePns;
    }

    public function addStructurePn(StructurePn $structurePn): static
    {
        if (!$this->structurePns->contains($structurePn)) {
            $this->structurePns->add($structurePn);
            $structurePn->setDiplome($this);
        }

        return $this;
    }

    public function removeStructurePn(StructurePn $structurePn): static
    {
        if ($this->structurePns->removeElement($structurePn)) {
            // set the owning side to null (unless already changed)
            if ($structurePn->getDiplome() === $this) {
                $structurePn->setDiplome(null);
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
}
