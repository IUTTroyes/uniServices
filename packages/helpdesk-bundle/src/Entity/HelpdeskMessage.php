<?php

namespace HelpdeskBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Users\Personnel;
use App\Repository\HelpDeskMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HelpDeskMessageRepository::class)]
#[ApiResource]
class HelpdeskMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $author = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'helpdeskMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HelpdeskTicket $ticket = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personnel $auteur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
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
