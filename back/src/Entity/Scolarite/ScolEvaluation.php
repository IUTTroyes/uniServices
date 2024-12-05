<?php

namespace App\Entity\Scolarite;

use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Repository\ScolEvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScolEvaluationRepository::class)]
class ScolEvaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(nullable: true)]
    private ?float $coeff = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?bool $visible = null;

    #[ORM\Column]
    private ?bool $modifiable = null;

    /**
     * @var Collection<int, Personnel>
     */
    #[ORM\ManyToMany(targetEntity: Personnel::class, inversedBy: 'scolEvaluations')]
    private Collection $personnelAutorise;

    #[ORM\ManyToOne(inversedBy: 'scolEvaluations')]
    private ?StructureAnneeUniversitaire $anneeUniv = null;

    #[ORM\ManyToOne(inversedBy: 'scolEvaluations')]
    private ?StructureSemestre $semestre = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'scolEvaluations')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $scolEvaluations;

    #[ORM\ManyToOne(inversedBy: 'scolEvaluations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ScolEnseignement $enseignement = null;

    /**
     * @var Collection<int, EtudiantNote>
     */
    #[ORM\OneToMany(targetEntity: EtudiantNote::class, mappedBy: 'evaluation')]
    private Collection $etudiantNotes;

    public function __construct()
    {
        $this->personnelAutorise = new ArrayCollection();
        $this->scolEvaluations = new ArrayCollection();
        $this->etudiantNotes = new ArrayCollection();
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getCoeff(): ?float
    {
        return $this->coeff;
    }

    public function setCoeff(?float $coeff): static
    {
        $this->coeff = $coeff;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isModifiable(): ?bool
    {
        return $this->modifiable;
    }

    public function setModifiable(bool $modifiable): static
    {
        $this->modifiable = $modifiable;

        return $this;
    }

    /**
     * @return Collection<int, Personnel>
     */
    public function getPersonnelAutorise(): Collection
    {
        return $this->personnelAutorise;
    }

    public function addPersonnelAutorise(Personnel $personnelAutorise): static
    {
        if (!$this->personnelAutorise->contains($personnelAutorise)) {
            $this->personnelAutorise->add($personnelAutorise);
        }

        return $this;
    }

    public function removePersonnelAutorise(Personnel $personnelAutorise): static
    {
        $this->personnelAutorise->removeElement($personnelAutorise);

        return $this;
    }

    public function getAnneeUniv(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniv;
    }

    public function setAnneeUniv(?StructureAnneeUniversitaire $anneeUniv): static
    {
        $this->anneeUniv = $anneeUniv;

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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getScolEvaluations(): Collection
    {
        return $this->scolEvaluations;
    }

    public function addScolEvaluation(self $scolEvaluation): static
    {
        if (!$this->scolEvaluations->contains($scolEvaluation)) {
            $this->scolEvaluations->add($scolEvaluation);
            $scolEvaluation->setParent($this);
        }

        return $this;
    }

    public function removeScolEvaluation(self $scolEvaluation): static
    {
        if ($this->scolEvaluations->removeElement($scolEvaluation)) {
            // set the owning side to null (unless already changed)
            if ($scolEvaluation->getParent() === $this) {
                $scolEvaluation->setParent(null);
            }
        }

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

    /**
     * @return Collection<int, EtudiantNote>
     */
    public function getEtudiantNotes(): Collection
    {
        return $this->etudiantNotes;
    }

    public function addEtudiantNote(EtudiantNote $etudiantNote): static
    {
        if (!$this->etudiantNotes->contains($etudiantNote)) {
            $this->etudiantNotes->add($etudiantNote);
            $etudiantNote->setEvaluation($this);
        }

        return $this;
    }

    public function removeEtudiantNote(EtudiantNote $etudiantNote): static
    {
        if ($this->etudiantNotes->removeElement($etudiantNote)) {
            // set the owning side to null (unless already changed)
            if ($etudiantNote->getEvaluation() === $this) {
                $etudiantNote->setEvaluation(null);
            }
        }

        return $this;
    }
}
