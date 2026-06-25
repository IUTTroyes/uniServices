<?php

namespace HelpdeskBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use HelpdeskBundle\Repository\HelpdeskCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Structure\StructureService;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: HelpdeskCategorieRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['category:read']],
            security: "is_granted('CAN_VIEW_CATEGORIE',object)",

        ),
        new GetCollection(
            uriTemplate: '/form_ticket/helpdesk_categories',
            normalizationContext: ['groups' => ['category:form_ticket']],
            security: "is_granted('CAN_VIEW_CATEGORIE',object)",

        )
    ]
)]
class HelpdeskCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:light','category:read','service:read','service:form_ticket','category:form_ticket','ticket:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category:light','category:read','service:read','service:form_ticket','category:form_ticket','ticket:read'])]
    private ?string $libelle = null;

    #[ORM\ManyToOne(targetEntity: self::class,inversedBy: 'enfants')]
    #[Groups(['category:read','service:read','service:form_ticket'])]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class,mappedBy: 'parent')]
    #[Groups(['category:read','service:read','service:form_ticket'])]
    private ?Collection $enfants;

    #[ORM\ManyToOne(inversedBy: 'helpdeskCategories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['category:read','ticket:read'])]
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

    public function getParent(): ?HelpdeskCategorie
    {
        return $this->parent;
    }

    public function setParent(?HelpdeskCategorie $parent): void
    {
        $this->parent = $parent;
    }

    public function getEnfants(): ?Collection
    {
        return $this->enfants;
    }

    public function setEnfants(?Collection $enfants): void
    {
        $this->enfants = $enfants;
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
