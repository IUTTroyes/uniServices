<?php

namespace App\Entity\Edt;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureCalendrier;
use App\Repository\Edt\EdtCreneauxInterditsSemaineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EdtCreneauxInterditsSemaineRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['edt_creneaux:read']]),
        new GetCollection(normalizationContext: ['groups' => ['edt_creneaux:read']]),
        new Post(securityPostDenormalize: "is_granted('CAN_EDIT_EDT_CRENEAUX', object)"),
        new Patch(securityPostDenormalize: "is_granted('CAN_EDIT_EDT_CRENEAUX', object)"),
        new Delete(security: "is_granted('CAN_DELETE_EDT_CRENEAUX', object)"),
    ]
)]
class EdtCreneauxInterditsSemaine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'creneauxInterditsSemaines')]
    private ?StructureCalendrier $semaine = null;

    #[ORM\ManyToOne(inversedBy: 'creneauxInterditsSemaines')]
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
