<?php

namespace IntranetBundle\Entity\Etudiant;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureUe;
use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;
use IntranetBundle\Repository\Etudiant\MoyenneUeRepository;

#[ORM\Entity(repositoryClass: MoyenneUeRepository::class)]
#[ApiResource]
class MoyenneUe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?StructureUe $ue = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?EtudiantScolariteSemestre $etudiantScolariteSemestre = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personnel $personnel = null;

    #[ORM\Column]
    private ?float $value = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEtudiantScolarite(): ?EtudiantScolariteSemestre
    {
        return $this->etudiantScolariteSemestre;
    }

    public function setEtudiantScolarite(?EtudiantScolariteSemestre $etudiantScolariteSemestre): static
    {
        $this->etudiantScolariteSemestre = $etudiantScolariteSemestre;

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

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }
}
