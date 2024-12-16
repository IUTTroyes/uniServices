<?php

namespace App\Entity\Scolarite;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Structure\StructureUe;
use App\Repository\Scolarite\ScolEnseignementUeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ScolEnseignementUeRepository::class)]
#[ApiResource]
class ScolEnseignementUe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['semestre:read:full'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'scolEnseignementUes')]
    #[Groups(['semestre:read:full'])]
    private ?ScolEnseignement $enseignement;

    #[ORM\ManyToOne(inversedBy: 'scolEnseignementUes')]
    private ?StructureUe $ue;

    #[ORM\Column]
    private float $ects = 0;

    #[ORM\Column]
    private float $coefficient = 0;

    public function __construct(ScolEnseignement $scolEnseignement, StructureUe $ue)
    {
        $this->enseignement = $scolEnseignement;
        $this->ue = $ue;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnseignement(): ?ScolEnseignement
    {
        return $this->enseignement;
    }

    public function setEnseignement(?ScolEnseignement $enseignement): static
    {
        $this->enseignement = $enseignement;

        return $this;
    }

    public function getUe(): ?StructureUe
    {
        return $this->ue;
    }

    public function setUe(?StructureUe $ue): static
    {
        $this->ue = $ue;

        return $this;
    }

    public function getEcts(): ?float
    {
        return $this->ects;
    }

    public function setEcts(float $ects): static
    {
        $this->ects = $ects;

        return $this;
    }

    public function getCoefficient(): ?float
    {
        return $this->coefficient;
    }

    public function setCoefficient(float $coefficient): static
    {
        $this->coefficient = $coefficient;

        return $this;
    }
}
