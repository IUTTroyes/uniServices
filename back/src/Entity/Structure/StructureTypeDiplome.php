<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Apc\ApcReferentiel;
use App\Repository\Type\TypeDiplomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeDiplomeRepository::class)]
#[ApiResource]
class StructureTypeDiplome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\Column(length: 20)]
    private string $sigle;

    #[ORM\Column]
    private bool $apc = false;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'typeDiplome')]
    private Collection $structureDiplomes;

    /**
     * @var Collection<int, ApcReferentiel>
     */
    #[ORM\OneToMany(targetEntity: ApcReferentiel::class, mappedBy: 'typeDiplome')]
    private Collection $apcReferentiels;

    public function __construct()
    {
        $this->structureDiplomes = new ArrayCollection();
        $this->apcReferentiels = new ArrayCollection();
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

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(string $sigle): static
    {
        $this->sigle = $sigle;

        return $this;
    }

    public function isApc(): ?bool
    {
        return $this->apc;
    }

    public function setApc(bool $apc = false): static
    {
        $this->apc = $apc;

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
            $structureDiplome->setTypeDiplome($this);
        }

        return $this;
    }

    public function removeStructureDiplome(StructureDiplome $structureDiplome): static
    {
        if ($this->structureDiplomes->removeElement($structureDiplome)) {
            // set the owning side to null (unless already changed)
            if ($structureDiplome->getTypeDiplome() === $this) {
                $structureDiplome->setTypeDiplome(null);
            }
        }

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
            $apcReferentiel->setTypeDiplome($this);
        }

        return $this;
    }

    public function removeApcReferentiel(ApcReferentiel $apcReferentiel): static
    {
        if ($this->apcReferentiels->removeElement($apcReferentiel)) {
            // set the owning side to null (unless already changed)
            if ($apcReferentiel->getTypeDiplome() === $this) {
                $apcReferentiel->setTypeDiplome(null);
            }
        }

        return $this;
    }
}
