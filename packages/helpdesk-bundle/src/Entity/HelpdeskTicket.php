<?php

namespace HelpdeskBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Users\Personnel;
use HelpdeskBundle\Enum\StatutTicketEnum;
use HelpdeskBundle\Filter\TicketFilter;
use HelpdeskBundle\Repository\HelpdeskTicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use HelpdeskBundle\State\Processor\TicketProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: HelpdeskTicketRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(TicketFilter::class)]
#[ApiFilter(SearchFilter::class, properties: [
    'subject' => 'partial',
    'description' => 'start',
    'auteur.display' => 'partial',
])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/helpdesk_tickets',
            inputFormats: [
                'jsonld' => ['application/ld+json'],
                'multipart' => ['multipart/form-data'],
            ],
            denormalizationContext: ['groups' => ['ticket:write']],
            security: "is_granted('CAN_CREATE_TICKET',object)",
            deserialize: false,
            processor: TicketProcessor::class,
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['ticket:read']],
        ),
        new Get(
            normalizationContext: ['groups' => ['ticket:read']],
        ),
        new Get(
            uriTemplate: '/simple-ticket/helpdesk_tickets/{id}',
            normalizationContext: ['groups' => ['simple-ticket:read']],
        ),
        new Patch(
            normalizationContext: ['groups' => ['ticket:write']],
        ),
        new Delete(
            normalizationContext: ['groups' => ['ticket:delete']],
        )
    ],
    paginationEnabled: false
)]
class HelpdeskTicket
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read','ticket:delete','simple-ticket:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['ticket:write','ticket:read','ticket:delete','simple-ticket:read'])]
    private ?string $subject = null;

    #[ORM\Column(type:Types::TEXT, nullable:true)]
    #[Groups(['ticket:write','ticket:read','ticket:delete','simple-ticket:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: StatutTicketEnum::class )]
    #[Groups(['ticket:read','ticket:write','ticket:delete','simple-ticket:read'])]
    private StatutTicketEnum $statut=StatutTicketEnum::A_TRAITER;

    #[Groups(['ticket:read','simple-ticket:read'])]
    public function getTransitionsAutorisees(): array
    {
        return $this->statut->getTransitionsAutorisees();
    }

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups(['ticket:write','ticket:read','ticket:delete'])]
    private ?string $priority = null;

    #[ORM\Column( nullable: true)]
    #[Groups(['ticket:write','ticket:read','ticket:delete','simple-ticket:read'])]
        private ?array $files_names = null;

    #[ORM\ManyToOne(inversedBy: 'ticket')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:write','ticket:read','ticket:delete','simple-ticket:read'])]
    private ?HelpdeskCategorie $helpdeskCategorie = null;

    /**
     * @var Collection<int, HelpdeskMessage>
     */
    #[ORM\OneToMany(targetEntity: HelpdeskMessage::class, mappedBy: 'ticket')]
    #[Groups(['ticket:write','ticket:read','ticket:delete'])]
    private Collection $helpdeskMessages;

    #[ORM\ManyToOne(inversedBy: 'helpdeskTickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:write','ticket:read','ticket:delete','message:read','message:write','simple-ticket:read'])]
    private ?Personnel $auteur = null;

    #[ORM\ManyToOne(inversedBy: 'helpdeskTickets')]
    #[Groups(['ticket:write','ticket:read','ticket:delete'])]
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

    public function getStatut(): StatutTicketEnum
    {
        return $this->statut;
    }

    public function setStatut(StatutTicketEnum $statut): void
    {
        $this->statut = $statut;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): void
    {
        $this->priority = $priority;
    }



    public function getFilesNames(): ?array
    {
        return $this->files_names;
    }

    public function setFilesNames(?array $files_names): void
    {
        $this->files_names = $files_names;
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

    public function getAuteur(): Personnel
    {
        return $this->auteur;
    }

    public function setAuteur(Personnel $auteur): static
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
