<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Edt\EdtCreneauxInterditsSemaine;
use App\Filter\SemaineFormationFilter;
use App\Repository\Structure\StructureCalendrierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureCalendrierRepository::class)]
#[ApiResource]
#[ApiFilter(SemaineFormationFilter::class)]
class StructureCalendrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'structureCalendriers')]
    private ?StructureAnneeUniversitaire $structureAnneeUniversitaire = null;

    #[ORM\Column]
    private ?int $semaineFormation = null;

    #[ORM\Column]
    private ?int $semaineReelle = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateLundi = null;

    /**
     * @var Collection<int, EdtCreneauxInterditsSemaine>
     */
    #[ORM\OneToMany(targetEntity: EdtCreneauxInterditsSemaine::class, mappedBy: 'semaine')]
    private Collection $edtCreneauxInterditsSemaines;

    public function __construct()
    {
        $this->edtCreneauxInterditsSemaines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSemaineFormation(): ?int
    {
        return $this->semaineFormation;
    }

    public function setSemaineFormation(int $semaineFormation): static
    {
        $this->semaineFormation = $semaineFormation;

        return $this;
    }

    public function getSemaineReelle(): ?int
    {
        return $this->semaineReelle;
    }

    public function setSemaineReelle(int $semaineReelle): static
    {
        $this->semaineReelle = $semaineReelle;

        return $this;
    }

    public function getDateLundi(): ?\DateTimeInterface
    {
        return $this->dateLundi;
    }

    public function setDateLundi(\DateTimeInterface $dateLundi): static
    {
        $this->dateLundi = $dateLundi;

        return $this;
    }

    public function getJours(): array
    {
        $jours = [];
        $date = clone $this->dateLundi;
        for ($i = 0; $i < 5; $i++) {
            $jours[] = clone $date;
            $date->modify('+1 day');
        }

        return $jours;
    }

    /**
     * @return Collection<int, EdtCreneauxInterditsSemaine>
     */
    public function getEdtCreneauxInterditsSemaines(): Collection
    {
        return $this->edtCreneauxInterditsSemaines;
    }

    public function addEdtCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $edtCreneauxInterditsSemaine): static
    {
        if (!$this->edtCreneauxInterditsSemaines->contains($edtCreneauxInterditsSemaine)) {
            $this->edtCreneauxInterditsSemaines->add($edtCreneauxInterditsSemaine);
            $edtCreneauxInterditsSemaine->setSemaine($this);
        }

        return $this;
    }

    public function removeEdtCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $edtCreneauxInterditsSemaine): static
    {
        if ($this->edtCreneauxInterditsSemaines->removeElement($edtCreneauxInterditsSemaine)) {
            // set the owning side to null (unless already changed)
            if ($edtCreneauxInterditsSemaine->getSemaine() === $this) {
                $edtCreneauxInterditsSemaine->setSemaine(null);
            }
        }

        return $this;
    }
}
