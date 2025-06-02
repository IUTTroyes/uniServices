<?php

namespace App\Entity\Personnel;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Filter\PersonnelEnseignantHrsFilter;
use App\Repository\PersonnelEnseignantHrsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PersonnelEnseignantHrsRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['enseignant_hrs:read']]),
        new GetCollection(normalizationContext: ['groups' => ['enseignant_hrs:read']]),
        new Post(normalizationContext: ['groups' => ['enseignant_hrs:write']],),
        new Delete(normalizationContext: ['groups' => ['enseignant_hrs:write']],)
    ]
)]
#[ApiFilter(PersonnelEnseignantHrsFilter::class)]
class PersonnelEnseignantHrs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['enseignant_hrs:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['enseignant_hrs:read'])]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'enseignantHrs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['enseignant_hrs:read'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'enseignantHrs')]
    #[Groups(['enseignant_hrs:read'])]
    private ?StructureSemestre $semestre = null;

    #[ORM\ManyToOne(inversedBy: 'enseignantHrs')]
    #[Groups(['enseignant_hrs:read'])]
    private ?StructureDiplome $diplome = null;

    #[ORM\Column]
    #[Groups(['enseignant_hrs:read'])]
    private ?float $nbHeuresTd = null;

    #[ORM\ManyToOne(inversedBy: 'enseignantHrs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['enseignant_hrs:read'])]
    private ?PersonnelEnseignantTypeHrs $enseignantTypeHrs = null;

    #[ORM\ManyToOne(inversedBy: 'enseignantHrs')]
    #[Groups(['enseignant_hrs:read'])]
    private ?StructureAnneeUniversitaire $annee_universitaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

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

    public function getSemestre(): ?StructureSemestre
    {
        return $this->semestre;
    }

    public function setSemestre(?StructureSemestre $semestre): static
    {
        $this->semestre = $semestre;

        return $this;
    }

    public function getDiplome(): ?StructureDiplome
    {
        return $this->diplome;
    }

    public function setDiplome(?StructureDiplome $diplome): static
    {
        $this->diplome = $diplome;

        return $this;
    }

    public function getNbHeuresTd(): ?float
    {
        return $this->nbHeuresTd;
    }

    public function setNbHeuresTd(float $nbHeuresTd): static
    {
        $this->nbHeuresTd = $nbHeuresTd;

        return $this;
    }

    public function getEnseignantTypeHrs(): ?PersonnelEnseignantTypeHrs
    {
        return $this->enseignantTypeHrs;
    }

    public function setEnseignantTypeHrs(?PersonnelEnseignantTypeHrs $enseignantTypeHrs): static
    {
        $this->enseignantTypeHrs = $enseignantTypeHrs;

        return $this;
    }

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->annee_universitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $annee_universitaire): static
    {
        $this->annee_universitaire = $annee_universitaire;

        return $this;
    }
}
