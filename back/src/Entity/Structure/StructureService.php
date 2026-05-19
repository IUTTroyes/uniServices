<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Users\Personnel;
use App\Repository\Structure\StructureServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use HelpdeskBundle\Entity\HelpdeskCategorie;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureServiceRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['service:read']],
        ),
        new GetCollection(
            uriTemplate: '/mini/structure_services',
            normalizationContext: ['groups' => ['service:light']],
        ),
        new GetCollection(
            uriTemplate: '/form_ticket/structure_services',
            normalizationContext: ['groups' => ['service:form_ticket']],
        )
    ]
)]

class StructureService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['service:read','service:light','service:form_ticket'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['service:read','service:light','service:form_ticket'])]
    private ?string $libelle = null;

    /**
     * @var Collection<int, HelpdeskCategorie>
     */
    #[ORM\OneToMany(targetEntity: HelpdeskCategorie::class, mappedBy: 'service', orphanRemoval: true)]
    #[Groups(['service:read','service:form_ticket'])]
    private Collection $helpdeskCategories;

    /**
     * @var Collection<int, Personnel>
     */
    #[ORM\ManyToMany(targetEntity: Personnel::class, inversedBy: 'structureServices')]
    #[Groups(['service:read'])]
    private Collection $personnel;

    public function __construct()
    {
        $this->helpdeskCategories = new ArrayCollection();
        $this->personnel = new ArrayCollection();
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

    /**
     * @return Collection<int, Personnel>
     */
    public function getPersonnel(): Collection
    {
        return $this->personnel;
    }

    public function addPersonnel(Personnel $personnel): static
    {
        if (!$this->personnel->contains($personnel)) {
            $this->personnel->add($personnel);
        }

        return $this;
    }

    public function removePersonnel(Personnel $personnel): static
    {
        $this->personnel->removeElement($personnel);

        return $this;
    }
}
