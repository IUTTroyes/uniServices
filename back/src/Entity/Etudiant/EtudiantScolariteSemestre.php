<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Structure\StructureSemestre;
use App\Repository\EtudiantScolariteSemestreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtudiantScolariteSemestreRepository::class)]
#[ApiResource]
class EtudiantScolariteSemestre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'etudiantScolariteSemestre', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?EtudiantScolarite $etudiant_scolarite = null;

    #[ORM\ManyToOne(inversedBy: 'etudiantScolariteSemestre', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?StructureSemestre $structure_semestre = null;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'etudiantScolariteSemestre')]
    private Collection $etudiant_absence;

    /**
     * @var Collection<int, EtudiantNote>
     */
    #[ORM\OneToMany(targetEntity: EtudiantNote::class, mappedBy: 'etudiantScolariteSemestre')]
    private Collection $etudiant_note;

    public function __construct()
    {
        $this->etudiant_absence = new ArrayCollection();
        $this->etudiant_note = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiantScolarite(): ?EtudiantScolarite
    {
        return $this->etudiant_scolarite;
    }

    public function setEtudiantScolarite(EtudiantScolarite $etudiant_scolarite): static
    {
        $this->etudiant_scolarite = $etudiant_scolarite;

        return $this;
    }

    public function getStructureSemestre(): ?StructureSemestre
    {
        return $this->structure_semestre;
    }

    public function setStructureSemestre(StructureSemestre $structure_semestre): static
    {
        $this->structure_semestre = $structure_semestre;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    public function getEtudiantAbsence(): Collection
    {
        return $this->etudiant_absence;
    }

    public function addEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if (!$this->etudiant_absence->contains($etudiantAbsence)) {
            $this->etudiant_absence->add($etudiantAbsence);
            $etudiantAbsence->setEtudiantScolariteSemestre($this);
        }

        return $this;
    }

    public function removeEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if ($this->etudiant_absence->removeElement($etudiantAbsence)) {
            // set the owning side to null (unless already changed)
            if ($etudiantAbsence->getEtudiantScolariteSemestre() === $this) {
                $etudiantAbsence->setEtudiantScolariteSemestre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtudiantNote>
     */
    public function getEtudiantNote(): Collection
    {
        return $this->etudiant_note;
    }

    public function addEtudiantNote(EtudiantNote $etudiantNote): static
    {
        if (!$this->etudiant_note->contains($etudiantNote)) {
            $this->etudiant_note->add($etudiantNote);
            $etudiantNote->setEtudiantScolariteSemestre($this);
        }

        return $this;
    }

    public function removeEtudiantNote(EtudiantNote $etudiantNote): static
    {
        if ($this->etudiant_note->removeElement($etudiantNote)) {
            // set the owning side to null (unless already changed)
            if ($etudiantNote->getEtudiantScolariteSemestre() === $this) {
                $etudiantNote->setEtudiantScolariteSemestre(null);
            }
        }

        return $this;
    }
}
