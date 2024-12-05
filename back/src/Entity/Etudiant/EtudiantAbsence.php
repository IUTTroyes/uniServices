<?php

namespace App\Entity\Etudiant;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Repository\EtudiantAbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtudiantAbsenceRepository::class)]
class EtudiantAbsence
{
    use UuidTrait;
    use EduSignTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateHeure = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $duree = null;

    #[ORM\Column]
    private ?bool $justifiee = null;

    #[ORM\ManyToOne(inversedBy: 'etudiantAbsences')]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'etudiantAbsences')]
    private ?Etudiant $etudiant = null;

    #[ORM\ManyToOne(inversedBy: 'etudiantAbsences')]
    private ?ScolEnseignement $enseignement = null;

    #[ORM\ManyToOne(inversedBy: 'etudiantAbsences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EtudiantScolarite $scolarite = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateJustification = null;

    #[ORM\ManyToOne(inversedBy: 'absence')]
    private ?EtudiantAbsenceJustificatif $etudiantAbsenceJustificatif = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateHeure(): ?\DateTimeInterface
    {
        return $this->dateHeure;
    }

    public function setDateHeure(\DateTimeInterface $dateHeure): static
    {
        $this->dateHeure = $dateHeure;

        return $this;
    }

    public function getDuree(): ?\DateTimeInterface
    {
        return $this->duree;
    }

    public function setDuree(\DateTimeInterface $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function isJustifiee(): ?bool
    {
        return $this->justifiee;
    }

    public function setJustifiee(bool $justifiee): static
    {
        $this->justifiee = $justifiee;

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

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): static
    {
        $this->etudiant = $etudiant;

        return $this;
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

    public function getScolarite(): ?EtudiantScolarite
    {
        return $this->scolarite;
    }

    public function setScolarite(?EtudiantScolarite $scolarite): static
    {
        $this->scolarite = $scolarite;

        return $this;
    }

    public function getDateJustification(): ?\DateTimeInterface
    {
        return $this->dateJustification;
    }

    public function setDateJustification(?\DateTimeInterface $dateJustification): static
    {
        $this->dateJustification = $dateJustification;

        return $this;
    }

    public function getEtudiantAbsenceJustificatif(): ?EtudiantAbsenceJustificatif
    {
        return $this->etudiantAbsenceJustificatif;
    }

    public function setEtudiantAbsenceJustificatif(?EtudiantAbsenceJustificatif $etudiantAbsenceJustificatif): static
    {
        $this->etudiantAbsenceJustificatif = $etudiantAbsenceJustificatif;

        return $this;
    }
}
