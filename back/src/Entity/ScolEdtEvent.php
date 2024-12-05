<?php

namespace App\Entity;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Personnel;
use App\Repository\ScolEdtEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScolEdtEventRepository::class)]
class ScolEdtEvent
{
    use UuidTrait;
    use EduSignTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $semaine_formation = null;

    #[ORM\Column(nullable: true)]
    private ?int $jour = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fin = null;

    #[ORM\Column(length: 25)]
    private ?string $salle = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $codeSalle = null;

    #[ORM\ManyToOne(inversedBy: 'scolEdtEvents')]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $code_personnel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libPersonnel = null;

    #[ORM\ManyToOne(inversedBy: 'scolEdtEvents')]
    private ?ScolEnseignement $enseignement = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codeModule = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $libModule = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $typeMatiere = null;

    #[ORM\ManyToOne(inversedBy: 'scolEdtEvents')]
    private ?StructureGroupe $groupe = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $codeGroupe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libGroupe = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(nullable: true)]
    private ?int $celcatId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'scolEdtEvents')]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\Column(nullable: true)]
    private ?int $departementCodeCelcat = null;

    #[ORM\ManyToOne(inversedBy: 'scolEdtEvents')]
    private ?StructureSemestre $semestre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedEvent = null;

    #[ORM\Column]
    private ?bool $evaluation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemaineFormation(): ?int
    {
        return $this->semaine_formation;
    }

    public function setSemaineFormation(?int $semaine_formation): static
    {
        $this->semaine_formation = $semaine_formation;

        return $this;
    }

    public function getJour(): ?int
    {
        return $this->jour;
    }

    public function setJour(?int $jour): static
    {
        $this->jour = $jour;

        return $this;
    }

    public function getDebut(): ?\DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(?\DateTimeInterface $debut): static
    {
        $this->debut = $debut;

        return $this;
    }

    public function getFin(): ?\DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(?\DateTimeInterface $fin): static
    {
        $this->fin = $fin;

        return $this;
    }

    public function getSalle(): ?string
    {
        return $this->salle;
    }

    public function setSalle(string $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getCodeSalle(): ?string
    {
        return $this->codeSalle;
    }

    public function setCodeSalle(?string $codeSalle): static
    {
        $this->codeSalle = $codeSalle;

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

    public function getCodePersonnel(): ?string
    {
        return $this->code_personnel;
    }

    public function setCodePersonnel(?string $code_personnel): static
    {
        $this->code_personnel = $code_personnel;

        return $this;
    }

    public function getLibPersonnel(): ?string
    {
        return $this->libPersonnel;
    }

    public function setLibPersonnel(?string $libPersonnel): static
    {
        $this->libPersonnel = $libPersonnel;

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

    public function getCodeModule(): ?string
    {
        return $this->codeModule;
    }

    public function setCodeModule(?string $codeModule): static
    {
        $this->codeModule = $codeModule;

        return $this;
    }

    public function getLibModule(): ?string
    {
        return $this->libModule;
    }

    public function setLibModule(?string $libModule): static
    {
        $this->libModule = $libModule;

        return $this;
    }

    public function getTypeMatiere(): ?string
    {
        return $this->typeMatiere;
    }

    public function setTypeMatiere(?string $typeMatiere): static
    {
        $this->typeMatiere = $typeMatiere;

        return $this;
    }

    public function getGroupe(): ?StructureGroupe
    {
        return $this->groupe;
    }

    public function setGroupe(?StructureGroupe $groupe): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getCodeGroupe(): ?string
    {
        return $this->codeGroupe;
    }

    public function setCodeGroupe(?string $codeGroupe): static
    {
        $this->codeGroupe = $codeGroupe;

        return $this;
    }

    public function getLibGroupe(): ?string
    {
        return $this->libGroupe;
    }

    public function setLibGroupe(?string $libGroupe): static
    {
        $this->libGroupe = $libGroupe;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getCelcatId(): ?int
    {
        return $this->celcatId;
    }

    public function setCelcatId(?int $celcatId): static
    {
        $this->celcatId = $celcatId;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

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

    public function getDepartementCodeCelcat(): ?int
    {
        return $this->departementCodeCelcat;
    }

    public function setDepartementCodeCelcat(?int $departementCodeCelcat): static
    {
        $this->departementCodeCelcat = $departementCodeCelcat;

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

    public function getUpdatedEvent(): ?\DateTimeInterface
    {
        return $this->updatedEvent;
    }

    public function setUpdatedEvent(?\DateTimeInterface $updatedEvent): static
    {
        $this->updatedEvent = $updatedEvent;

        return $this;
    }

    public function isEvaluation(): ?bool
    {
        return $this->evaluation;
    }

    public function setEvaluation(bool $evaluation): static
    {
        $this->evaluation = $evaluation;

        return $this;
    }
}
