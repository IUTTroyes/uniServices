<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\Structure\StructureCalendrierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureCalendrierRepository::class)]
#[ApiResource]
class StructureCalendrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'structureCalendriers')]
    private ?StructureAnneeUniversitaire $structureAnneeUniversitaire = null;

    #[ORM\Column]
    private ?int $semaineFormation = null;

    #[ORM\Column]
    private ?int $semaineReelle = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateLundi = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStructureAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->structureAnneeUniversitaire;
    }

    public function setStructureAnneeUniversitaire(?StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        $this->structureAnneeUniversitaire = $structureAnneeUniversitaire;

        return $this;
    }

    public function getSemaineFormation(): ?int
    {
        return $this->semaineFormation;
    }

    public function setSemaineFormation(int $semaineFormation): static
    {
        $this->semaineFormation = $semaineFormation;

        return $this;
    }

    public function getSemaineReelle(): ?int
    {
        return $this->semaineReelle;
    }

    public function setSemaineReelle(int $semaineReelle): static
    {
        $this->semaineReelle = $semaineReelle;

        return $this;
    }

    public function getDateLundi(): ?\DateTimeInterface
    {
        return $this->dateLundi;
    }

    public function setDateLundi(\DateTimeInterface $dateLundi): static
    {
        $this->dateLundi = $dateLundi;

        return $this;
    }
}
