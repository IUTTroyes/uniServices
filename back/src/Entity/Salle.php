<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SalleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SalleRepository::class)]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(normalizationContext: ['groups' => ['salle:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['salle:detail']]),
    ],
    order: ['libelle' => 'ASC'],
)]
class Salle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['salle:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['salle:detail'])]
    private ?string $libelle = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['salle:detail'])]
    private ?int $capacite = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['salle:detail'])]
    private ?string $type = null;

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

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
