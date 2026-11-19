<?php

namespace IntranetBundle\Entity\Etudiant;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;
use IntranetBundle\Repository\Etudiant\MoyenneEnseignementRepository;

#[ORM\Entity(repositoryClass: MoyenneEnseignementRepository::class)]
/** @deprecated Données historiques uniquement ; les résultats officiels sont figés par révision de sous-commission. */
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

    public function getEtudiantScolariteSemestre(): ?EtudiantScolariteSemestre
    {
        return $this->etudiantScolariteSemestre;
    }

    public function setEtudiantScolariteSemestre(?EtudiantScolariteSemestre $etudiantScolariteSemestre): static
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
