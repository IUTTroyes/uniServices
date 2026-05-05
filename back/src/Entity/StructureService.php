<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\StructureServiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureServiceRepository::class)]
#[ApiResource]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50)]
    private ?string $personnel = null;

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
}
