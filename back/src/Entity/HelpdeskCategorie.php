<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\HelpdeskCategorieRepository;
use Doctrine\ORM\Mapping as ORM;

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
}
