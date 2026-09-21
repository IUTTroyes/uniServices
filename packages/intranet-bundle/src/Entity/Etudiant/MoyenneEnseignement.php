<?php

namespace IntranetBundle\Entity\Etudiant;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;
use IntranetBundle\Repository\Etudiant\MoyenneEnseignementRepository;

#[ORM\Entity(repositoryClass: MoyenneEnseignementRepository::class)]
#[ApiResource]
class MoyenneEnseignement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ScolEnseignement $enseignement = null;

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

    public function getEnseignement(): ?ScolEnseignement
    {
        return $this->enseignement;
    }

    public function setEnseignement(?ScolEnseignement $enseignement): static
    {
        $this->enseignement = $enseignement;

        return $this;
    }

    public function getEtudiantScolarite(): ?EtudiantScolariteSemestre
    {
        return $this->etudiantScolariteSemestre;
    }

    public function setEtudiantScolarite(?EtudiantScolariteSemestre $etudiant_scolarite): static
    {
        $this->etudiantScolariteSemestre = $etudiant_scolarite;

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
