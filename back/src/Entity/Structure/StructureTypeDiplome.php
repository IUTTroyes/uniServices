<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Apc\ApcReferentiel;
use App\Repository\Structure\StructureTypeDiplomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureTypeDiplomeRepository::class)]
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
    #[Groups(['diplome:read'])]
    private string $sigle;

    #[ORM\Column]
    #[Groups(['diplome:read'])]
    private bool $apc = false;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'typeDiplome')]
    private Collection $diplomes;

    /**
     * @var Collection<int, ApcReferentiel>
     */
    #[ORM\OneToMany(targetEntity: ApcReferentiel::class, mappedBy: 'typeDiplome')]
    private Collection $referentiels;

    public function __construct()
    {
        $this->diplomes = new ArrayCollection();
        $this->referentiels = new ArrayCollection();
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
    public function getDiplomes(): Collection
    {
        return $this->diplomes;
    }

    public function addDiplome(StructureDiplome $diplome): static
    {
        if (!$this->diplomes->contains($diplome)) {
            $this->diplomes->add($diplome);
            $diplome->setTypeDiplome($this);
        }

        return $this;
    }

    public function removeDiplome(StructureDiplome $diplome): static
    {
        if ($this->diplomes->removeElement($diplome)) {
            // set the owning side to null (unless already changed)
            if ($diplome->getTypeDiplome() === $this) {
                $diplome->setTypeDiplome(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApcReferentiel>
     */
    public function getReferentiels(): Collection
    {
        return $this->referentiels;
    }

    public function addReferentiel(ApcReferentiel $referentiel): static
    {
        if (!$this->referentiels->contains($referentiel)) {
            $this->referentiels->add($referentiel);
            $referentiel->setTypeDiplome($this);
        }

        return $this;
    }

    public function removeReferentiel(ApcReferentiel $referentiel): static
    {
        if ($this->referentiels->removeElement($referentiel)) {
            // set the owning side to null (unless already changed)
            if ($referentiel->getTypeDiplome() === $this) {
                $referentiel->setTypeDiplome(null);
            }
        }

        return $this;
    }
}
