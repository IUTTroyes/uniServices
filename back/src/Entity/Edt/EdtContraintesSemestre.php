<?php

namespace App\Entity\Edt;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Repository\Edt\EdtContraintesSemestreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EdtContraintesSemestreRepository::class)]
#[ApiResource]
class EdtContraintesSemestre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contraintesSemestres')]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\ManyToOne(inversedBy: 'contraintesSemestres')]
    private ?StructureSemestre $semestre = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['semestre-test:read'])]
    private ?array $contraintes = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSemestre(): ?StructureSemestre
    {
        return $this->semestre;
    }

    public function setSemestre(?StructureSemestre $semestre): static
    {
        $this->semestre = $semestre;

        return $this;
    }

    public function getContraintes(): ?array
    {
        return $this->contraintes ?? [];
    }

    public function setContraintes(?array $contraintes): static
    {
        $this->contraintes = $contraintes;

        return $this;
    }
}
