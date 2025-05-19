<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Structure\StructureSemestre;
use App\Repository\EtudiantScolariteSemestreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtudiantScolariteSemestreRepository::class)]
#[ApiResource]
class EtudiantScolariteSemestre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'scolariteSemestre', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scolarite:read', 'etudiant:read'])]
    private ?StructureSemestre $semestre = null;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'semestre')]
    private Collection $absence;

    /**
     * @var Collection<int, EtudiantNote>
     */
    #[ORM\OneToMany(targetEntity: EtudiantNote::class, mappedBy: 'semestre')]
    private Collection $note;

    #[ORM\ManyToOne(inversedBy: 'scolariteSemestre')]
    private ?EtudiantScolarite $scolarite = null;

    public function __construct()
    {
        $this->absence = new ArrayCollection();
        $this->note = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemestre(): ?StructureSemestre
    {
        return $this->semestre;
    }

    public function setSemestre(StructureSemestre $semestre): static
    {
        $this->semestre = $semestre;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    public function getAbsence(): Collection
    {
        return $this->absence;
    }

    public function addAbsence(EtudiantAbsence $absence): static
    {
        if (!$this->absence->contains($absence)) {
            $this->absence->add($absence);
            $absence->setScolariteSemestre($this);
        }

        return $this;
    }

    public function removeAbsence(EtudiantAbsence $absence): static
    {
        if ($this->absence->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getScolariteSemestre() === $this) {
                $absence->setScolariteSemestre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtudiantNote>
     */
    public function getNote(): Collection
    {
        return $this->note;
    }

    public function addNote(EtudiantNote $note): static
    {
        if (!$this->note->contains($note)) {
            $this->note->add($note);
            $note->setScolariteSemestre($this);
        }

        return $this;
    }

    public function removeNote(EtudiantNote $note): static
    {
        if ($this->note->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getScolariteSemestre() === $this) {
                $note->setScolariteSemestre(null);
            }
        }

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
}
