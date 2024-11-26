<?php

namespace App\Entity\Structure;

use App\Repository\StructureScolariteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureScolariteRepository::class)]
class StructureScolarite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'structureScolarites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StructureSemestre $semestre = null;

    #[ORM\ManyToOne(inversedBy: 'structureScolarites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StructureEtudiant $etudiant = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $proposition = null;

    #[ORM\Column(nullable: true)]
    private ?float $moyenne = null;

    #[ORM\Column]
    private ?int $nb_absences = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private ?bool $public = null;

    #[ORM\Column(nullable: true)]
    private ?array $moyennes_matiere = null;

    #[ORM\Column(nullable: true)]
    private ?array $moyennes_ue = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\ManyToOne(inversedBy: 'scolarit�es')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StructureAnneeUniversitaire $structureAnneeUniversitaire = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEtudiant(): ?StructureEtudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?StructureEtudiant $etudiant): static
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getProposition(): ?string
    {
        return $this->proposition;
    }

    public function setProposition(?string $proposition): static
    {
        $this->proposition = $proposition;

        return $this;
    }

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function setMoyenne(?float $moyenne): static
    {
        $this->moyenne = $moyenne;

        return $this;
    }

    public function getNbAbsences(): ?int
    {
        return $this->nb_absences;
    }

    public function setNbAbsences(int $nb_absences): static
    {
        $this->nb_absences = $nb_absences;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getMoyennesMatiere(): ?array
    {
        return $this->moyennes_matiere;
    }

    public function setMoyennesMatiere(?array $moyennes_matiere): static
    {
        $this->moyennes_matiere = $moyennes_matiere;

        return $this;
    }

    public function getMoyennesUe(): ?array
    {
        return $this->moyennes_ue;
    }

    public function setMoyennesUe(?array $moyennes_ue): static
    {
        $this->moyennes_ue = $moyennes_ue;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
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
}
