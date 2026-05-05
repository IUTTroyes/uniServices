<?php

namespace HelpdeskBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\HelpdeskCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Structure\StructureService;

#[ORM\Entity(repositoryClass: HelpdeskCategorieRepository::class)]
#[ApiResource]
class HelpdeskCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $libelle = null;

    #[ORM\Column(length: 20)]
    private ?string $parent = null;

    #[ORM\Column(length: 20)]
    private ?string $enfant = null;

    #[ORM\ManyToOne(inversedBy: 'helpdeskCategories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StructureService $service = null;

    /**
     * @var Collection<int, HelpdeskTicket>
     */
    #[ORM\OneToMany(targetEntity: HelpdeskTicket::class, mappedBy: 'helpdeskCategorie', orphanRemoval: true)]
    private Collection $ticket;

    public function __construct()
    {
        $this->ticket = new ArrayCollection();
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

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(string $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getEnfant(): ?string
    {
        return $this->enfant;
    }

    public function setEnfant(string $enfant): static
    {
        $this->enfant = $enfant;

        return $this;
    }

    public function getService(): ?StructureService
    {
        return $this->service;
    }

    public function setService(?StructureService $service): static
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return Collection<int, HelpdeskTicket>
     */
    public function getTicket(): Collection
    {
        return $this->ticket;
    }

    public function addTicket(HelpdeskTicket $ticket): static
    {
        if (!$this->ticket->contains($ticket)) {
            $this->ticket->add($ticket);
            $ticket->setHelpdeskCategorie($this);
        }

        return $this;
    }

    public function removeTicket(HelpdeskTicket $ticket): static
    {
        if ($this->ticket->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getHelpdeskCategorie() === $this) {
                $ticket->setHelpdeskCategorie(null);
            }
        }

        return $this;
    }
}
