<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\StructureDepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureDepartementRepository::class)]
#[ApiResource]
class StructureDepartement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo_name = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $tel_contact = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $site_web = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column]
    private array $opt = [];

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'departement')]
    private Collection $structureDiplomes;

    #[ORM\OneToOne(mappedBy: 'departement', cascade: ['persist', 'remove'])]
    private ?StructureDepartementPersonnel $structureDepartementPersonnel = null;

    /**
     * @var Collection<int, StructureDepartementPersonnel>
     */
    #[ORM\OneToMany(targetEntity: StructureDepartementPersonnel::class, mappedBy: 'departement')]
    private Collection $structureDepartementPersonnels;

    public function __construct()
    {
        $this->structureDiplomes = new ArrayCollection();
        $this->structureDepartementPersonnels = new ArrayCollection();
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

    public function getLogoName(): ?string
    {
        return $this->logo_name;
    }

    public function setLogoName(?string $logo_name): static
    {
        $this->logo_name = $logo_name;

        return $this;
    }

    public function getTelContact(): ?string
    {
        return $this->tel_contact;
    }

    public function setTelContact(?string $tel_contact): static
    {
        $this->tel_contact = $tel_contact;

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

    public function getSiteWeb(): ?string
    {
        return $this->site_web;
    }

    public function setSiteWeb(?string $site_web): static
    {
        $this->site_web = $site_web;

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

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

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
     * @return Collection<int, StructureDiplome>
     */
    public function getStructureDiplomes(): Collection
    {
        return $this->structureDiplomes;
    }

    public function addStructureDiplome(StructureDiplome $structureDiplome): static
    {
        if (!$this->structureDiplomes->contains($structureDiplome)) {
            $this->structureDiplomes->add($structureDiplome);
            $structureDiplome->setDepartement($this);
        }

        return $this;
    }

    public function removeStructureDiplome(StructureDiplome $structureDiplome): static
    {
        if ($this->structureDiplomes->removeElement($structureDiplome)) {
            // set the owning side to null (unless already changed)
            if ($structureDiplome->getDepartement() === $this) {
                $structureDiplome->setDepartement(null);
            }
        }

        return $this;
    }

    public function getStructureDepartementPersonnel(): ?StructureDepartementPersonnel
    {
        return $this->structureDepartementPersonnel;
    }

    public function setStructureDepartementPersonnel(StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        // set the owning side of the relation if necessary
        if ($structureDepartementPersonnel->getDepartementId() !== $this) {
            $structureDepartementPersonnel->setDepartementId($this);
        }

        $this->structureDepartementPersonnel = $structureDepartementPersonnel;

        return $this;
    }

    /**
     * @return Collection<int, StructureDepartementPersonnel>
     */
    public function getStructureDepartementPersonnels(): Collection
    {
        return $this->structureDepartementPersonnels;
    }

    public function addStructureDepartementPersonnel(StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        if (!$this->structureDepartementPersonnels->contains($structureDepartementPersonnel)) {
            $this->structureDepartementPersonnels->add($structureDepartementPersonnel);
            $structureDepartementPersonnel->setDepartement($this);
        }

        return $this;
    }

    public function removeStructureDepartementPersonnel(StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        if ($this->structureDepartementPersonnels->removeElement($structureDepartementPersonnel)) {
            // set the owning side to null (unless already changed)
            if ($structureDepartementPersonnel->getDepartement() === $this) {
                $structureDepartementPersonnel->setDepartement(null);
            }
        }

        return $this;
    }
}
