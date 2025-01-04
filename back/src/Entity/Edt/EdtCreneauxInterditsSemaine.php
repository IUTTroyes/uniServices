<?php

namespace App\Entity\Edt;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureCalendrier;
use App\Repository\Edt\EdtCreneauxInterditsSemaineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EdtCreneauxInterditsSemaineRepository::class)]
class EdtCreneauxInterditsSemaine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'edtCreneauxInterditsSemaines')]
    private ?StructureCalendrier $semaine = null;

    #[ORM\ManyToOne(inversedBy: 'edtCreneauxInterditsSemaines')]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\Column]
    private array $restrictedSlots = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemaine(): ?StructureCalendrier
    {
        return $this->semaine;
    }

    public function setSemaine(?StructureCalendrier $semaine): static
    {
        $this->semaine = $semaine;

        return $this;
    }

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

        return $this;
    }

    public function getRestrictedSlots(): array
    {
        return $this->restrictedSlots;
    }

    public function setRestrictedSlots(array $restrictedSlots): static
    {
        $this->restrictedSlots = $restrictedSlots;

        return $this;
    }
}
