<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\HelpdeskTicketRepository;
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
}
