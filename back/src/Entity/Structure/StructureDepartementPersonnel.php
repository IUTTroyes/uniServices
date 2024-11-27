<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Users\Personnel;
use App\Repository\StructureDepartementPersonnelRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureDepartementPersonnelRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['structure_departement_personnel:read']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_departement_personnel:read']]),
    ]
)]
class StructureDepartementPersonnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?bool $defaut = null;

    #[ORM\ManyToOne(inversedBy: 'structureDepartementPersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'structureDepartementPersonnels')]
    #[Groups(groups: ['personnel:read'])]
    private ?StructureDepartement $departement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function isDefaut(): ?bool
    {
        return $this->defaut;
    }

    public function setDefaut(bool $defaut): static
    {
        $this->defaut = $defaut;

        return $this;
    }

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }
}
