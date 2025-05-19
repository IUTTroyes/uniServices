<?php

namespace App\Entity\Stages;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Repository\Stages\StagePeriodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StagePeriodeRepository::class)]
#[ApiResource]
class StagePeriode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'stagePeriodes')]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\ManyToOne(inversedBy: 'stagePeriodes')]
    private ?StructureSemestre $semestreProgramme = null;

    /**
     * @var Collection<int, StructureSemestre>
     */
    #[ORM\ManyToMany(targetEntity: StructureSemestre::class, inversedBy: 'stagePeriodes')]
    private Collection $semestresSaisie;

    #[ORM\Column]
    private ?int $nbSemaines = null;

    #[ORM\Column]
    private ?int $nbJours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    public function __construct()
    {
        $this->semestresSaisie = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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

    public function getSemestreProgramme(): ?StructureSemestre
    {
        return $this->semestreProgramme;
    }

    public function setSemestreProgramme(?StructureSemestre $semestreProgramme): static
    {
        $this->semestreProgramme = $semestreProgramme;

        return $this;
    }

    /**
     * @return Collection<int, StructureSemestre>
     */
    public function getSemestresSaisie(): Collection
    {
        return $this->semestresSaisie;
    }

    public function addSemestresSaisie(StructureSemestre $semestresSaisie): static
    {
        if (!$this->semestresSaisie->contains($semestresSaisie)) {
            $this->semestresSaisie->add($semestresSaisie);
        }

        return $this;
    }

    public function removeSemestresSaisie(StructureSemestre $semestresSaisie): static
    {
        $this->semestresSaisie->removeElement($semestresSaisie);

        return $this;
    }

    public function getNbSemaines(): ?int
    {
        return $this->nbSemaines;
    }

    public function setNbSemaines(int $nbSemaines): static
    {
        $this->nbSemaines = $nbSemaines;

        return $this;
    }

    public function getNbJours(): ?int
    {
        return $this->nbJours;
    }

    public function setNbJours(int $nbJours): static
    {
        $this->nbJours = $nbJours;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }
}
