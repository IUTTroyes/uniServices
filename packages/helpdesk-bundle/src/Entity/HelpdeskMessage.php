<?php

namespace HelpdeskBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Users\Personnel;
use HelpdeskBundle\Repository\HelpDeskMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: HelpDeskMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(

    operations: [
    new Get(
        normalizationContext: ['groups' => ['message:read']],
    ),]
)
]
class HelpdeskMessage
{
    use LifeCycleTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read'])]

    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['message:read'])]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'helpdeskMessages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read'])]

    private ?HelpdeskTicket $ticket = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['message:read'])]

    private ?Personnel $auteur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getTicket(): ?HelpdeskTicket
    {
        return $this->ticket;
    }

    public function setTicket(?HelpdeskTicket $ticket): static
    {
        $this->ticket = $ticket;

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
}
