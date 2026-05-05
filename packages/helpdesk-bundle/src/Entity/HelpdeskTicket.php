<?php

namespace HelpdeskBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Users\Personnel;
use App\Repository\HelpdeskTicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HelpdeskTicketRepository::class)]
#[ApiResource]
class HelpdeskTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $subject = null;

    #[ORM\Column(length: 150)]
    private ?string $description = null;

    #[ORM\Column(length: 15)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $priority = null;

    #[ORM\Column(length: 20)]
    private ?string $category = null;

    #[ORM\Column(length: 50)]
    private ?string $creator = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $assigned = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(length: 255)]
    private ?string $LifeCycle = null;

    #[ORM\ManyToOne(inversedBy: 'ticket')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HelpdeskCategorie $helpdeskCategorie = null;

    /**
     * @var Collection<int, HelpdeskMessage>
     */
    #[ORM\OneToMany(targetEntity: HelpdeskMessage::class, mappedBy: 'ticket')]
    private Collection $helpdeskMessages;

    #[ORM\ManyToOne(inversedBy: 'helpdeskTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personnel $auteur = null;

    #[ORM\ManyToOne(inversedBy: 'helpdeskTickets')]
    private ?Personnel $assigne = null;

    public function __construct()
    {
        $this->helpdeskMessages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getAssigned(): ?string
    {
        return $this->assigned;
    }

    public function setAssigned(?string $assigned): static
    {
        $this->assigned = $assigned;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getLifeCycle(): ?string
    {
        return $this->LifeCycle;
    }

    public function setLifeCycle(string $LifeCycle): static
    {
        $this->LifeCycle = $LifeCycle;

        return $this;
    }

    public function getHelpdeskCategorie(): ?HelpdeskCategorie
    {
        return $this->helpdeskCategorie;
    }

    public function setHelpdeskCategorie(?HelpdeskCategorie $helpdeskCategorie): static
    {
        $this->helpdeskCategorie = $helpdeskCategorie;

        return $this;
    }

    /**
     * @return Collection<int, HelpdeskMessage>
     */
    public function getHelpdeskMessages(): Collection
    {
        return $this->helpdeskMessages;
    }

    public function addHelpdeskMessage(HelpdeskMessage $helpdeskMessage): static
    {
        if (!$this->helpdeskMessages->contains($helpdeskMessage)) {
            $this->helpdeskMessages->add($helpdeskMessage);
            $helpdeskMessage->setTicket($this);
        }

        return $this;
    }

    public function removeHelpdeskMessage(HelpdeskMessage $helpdeskMessage): static
    {
        if ($this->helpdeskMessages->removeElement($helpdeskMessage)) {
            // set the owning side to null (unless already changed)
            if ($helpdeskMessage->getTicket() === $this) {
                $helpdeskMessage->setTicket(null);
            }
        }

        return $this;
    }

    public function getAuteur(): ?Personnel
    {
        return $this->auteur;
    }

    public function setAuteur(?Personnel $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getAssigne(): ?Personnel
    {
        return $this->assigne;
    }

    public function setAssigne(?Personnel $assigne): static
    {
        $this->assigne = $assigne;

        return $this;
    }
}
