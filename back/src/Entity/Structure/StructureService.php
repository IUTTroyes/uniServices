<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\Structure\StructureServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use HelpdeskBundle\Entity\HelpdeskCategorie;

#[ORM\Entity(repositoryClass: StructureServiceRepository::class)]
#[ApiResource]
class StructureService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50)]
    private ?string $personnel = null;

    /**
     * @var Collection<int, HelpdeskCategorie>
     */
    #[ORM\OneToMany(targetEntity: HelpdeskCategorie::class, mappedBy: 'service', orphanRemoval: true)]
    private Collection $helpdeskCategories;

    public function __construct()
    {
        $this->helpdeskCategories = new ArrayCollection();
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

    public function getPersonnel(): ?string
    {
        return $this->personnel;
    }

    public function setPersonnel(string $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    /**
     * @return Collection<int, HelpdeskCategorie>
     */
    public function getHelpdeskCategories(): Collection
    {
        return $this->helpdeskCategories;
    }

    public function addHelpdeskCategory(HelpdeskCategorie $helpdeskCategory): static
    {
        if (!$this->helpdeskCategories->contains($helpdeskCategory)) {
            $this->helpdeskCategories->add($helpdeskCategory);
            $helpdeskCategory->setService($this);
        }

        return $this;
    }

    public function removeHelpdeskCategory(HelpdeskCategorie $helpdeskCategory): static
    {
        if ($this->helpdeskCategories->removeElement($helpdeskCategory)) {
            // set the owning side to null (unless already changed)
            if ($helpdeskCategory->getService() === $this) {
                $helpdeskCategory->setService(null);
            }
        }

        return $this;
    }
}
