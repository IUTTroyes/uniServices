<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Filter\EtudiantScolariteSemestreFilter;
use App\Repository\EtudiantScolariteSemestreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtudiantScolariteSemestreRepository::class)]
#[ApiFilter(EtudiantScolariteSemestreFilter::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['scolarite-semestre:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['scolarite-semestre:detail', 'semestre:light', 'annee:light', 'groupe:light']]),
        new Patch(normalizationContext: ['groups' => ['scolarite-semestre:detail']], securityPostDenormalize: "is_granted('CAN_EDIT_SCOL', object)"),
    ]
)]
class EtudiantScolariteSemestre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['scolarite-semestre:detail', 'etudiant:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'scolariteSemestre', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scolarite-semestre:detail', 'etudiant:read'])]
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

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, inversedBy: 'scolariteSemestres')]
    #[Groups(['scolarite-semestre:detail'])]
    private Collection $groupes;

    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite-semestre:detail'])]
    private ?float $moyenne = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite-semestre:detail'])]
    private ?array $moyennesMatiere = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite-semestre:detail'])]
    private ?array $moyennesUe = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite-semestre:detail'])]
    private ?bool $decision = null;

    #[ORM\ManyToOne(inversedBy: 'scolariteSemestre', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['scolarite-semestre:detail', 'etudiant:read'])]
    private ?StructureSemestre $proposition = null;

    public function __construct()
    {
        $this->absence = new ArrayCollection();
        $this->note = new ArrayCollection();
        $this->groupes = new ArrayCollection();
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

    /**
     * @return Collection<int, StructureGroupe>
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(StructureGroupe $groupe): static
    {
        if (!$this->groupes->contains($groupe)) {
            $this->groupes->add($groupe);
        }

        return $this;
    }

    public function removeGroupe(StructureGroupe $groupe): static
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function setMoyenne(?float $moyenne): void
    {
        $this->moyenne = $moyenne;
    }

    public function getMoyennesMatiere(): ?array
    {
        return $this->moyennesMatiere;
    }

    public function setMoyennesMatiere(?array $moyennesMatiere): void
    {
        $this->moyennesMatiere = $moyennesMatiere;
    }

    public function getMoyennesUe(): ?array
    {
        return $this->moyennesUe;
    }

    public function setMoyennesUe(?array $moyennesUe): void
    {
        $this->moyennesUe = $moyennesUe;
    }

    public function getDecision(): ?bool
    {
        return $this->decision;
    }

    public function setDecision(?bool $decision): void
    {
        $this->decision = $decision;
    }

    public function getProposition(): ?StructureSemestre
    {
        return $this->proposition;
    }

    public function setProposition(?StructureSemestre $proposition): void
    {
        $this->proposition = $proposition;
    }
}
