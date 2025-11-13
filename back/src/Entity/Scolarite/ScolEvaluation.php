<?php

namespace App\Entity\Scolarite;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Personnel;
use App\Enum\TypeEvaluationEnum;
use App\Enum\TypeGroupeEnum;
use App\Filter\EvaluationFilter;
use App\Repository\ScolEvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ScolEvaluationRepository::class)]
#[ApiFilter(EvaluationFilter::class)]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(normalizationContext: ['groups' => ['evaluation:detail', 'enseignement:light']]),
        new GetCollection(normalizationContext: ['groups' => ['evaluation:detail', 'personnel:light']]),
        new Get(
            uriTemplate: '/mini/scol_evaluations/{id}',
            normalizationContext: ['groups' => ['evaluation:light']],
        ),
        new Get(
            uriTemplate: '/maxi/scol_evaluations/{id}',
            normalizationContext: ['groups' => ['evaluation:detail']],
        ),
        new Patch(normalizationContext: ['groups' => ['evaluation:write']], securityPostDenormalize: "is_granted('CAN_EDIT_EVAL', object)"),
    ]
)]
class ScolEvaluation
{
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['evaluation:light', 'evaluation:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evaluation:light', 'evaluation:detail', 'evaluation:write'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private ?string $commentaire = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private ?float $coeff = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private ?bool $visible = null;

    #[ORM\Column]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private ?bool $modifiable = null;

    #[ORM\Column(length: 25, enumType: TypeEvaluationEnum::class, nullable: true)]
    #[Groups(['evaluation:detail', 'evaluation:write', 'evaluation:write'])]
    private ?TypeEvaluationEnum $type = null;

    /**
     * @var Collection<int, Personnel>
     */
    #[ORM\ManyToMany(targetEntity: Personnel::class, inversedBy: 'evaluations')]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private Collection $personnelAutorise;

    #[ORM\ManyToOne(inversedBy: 'evaluations')]
    #[Groups(['evaluation:detail'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\ManyToOne(inversedBy: 'evaluations')]
    #[Groups(['evaluation:detail'])]
    private ?StructureSemestre $semestre = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'evaluations')]
    #[Groups(['evaluation:detail'])]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[Groups(['evaluation:detail'])]
    private Collection $evaluations;

    #[ORM\ManyToOne(inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['evaluation:detail'])]
    private ?ScolEnseignement $enseignement = null;

    /**
     * @var Collection<int, EtudiantNote>
     */
    #[ORM\OneToMany(targetEntity: EtudiantNote::class, mappedBy: 'evaluation')]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private Collection $notes;

    #[ORM\Column(length: 10, enumType: TypeGroupeEnum::class, nullable: true)]
    #[\Symfony\Component\Serializer\Attribute\Groups(['evaluation:detail'])]
    #[Groups(['evaluation:detail', 'evaluation:write'])]
    private ?TypeGroupeEnum $typeGroupe;

    public function __construct()
    {
        $this->personnelAutorise = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->notes = new ArrayCollection();
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

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

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
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(self $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setParent($this);
        }

        return $this;
    }

    public function removeEvaluation(self $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getParent() === $this) {
                $evaluation->setParent(null);
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
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(EtudiantNote $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setEvaluation($this);
        }

        return $this;
    }

    public function removeNote(EtudiantNote $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getEvaluation() === $this) {
                $note->setEvaluation(null);
            }
        }

        return $this;
    }

    public function getType(): ?TypeEvaluationEnum
    {
        return $this->type;
    }

    public function setType(?TypeEvaluationEnum $type): void
    {
        $this->type = $type;
    }

    #[Groups(['evaluation:detail'])]
    public function getTypeIcon(): ?string
    {
        if (null === $this->type) {
            return null;
        }

        $severityMap = TypeEvaluationEnum::getIcon();

        return $severityMap[$this->type->value] ?? null;
    }

    #[Groups(['evaluation:detail'])]
    public function getEtat(): string
    {
        if (null === $this->coeff && $this->personnelAutorise->isEmpty() && null === $this->typeGroupe) {
            return 'non_initialisee';
        } else {
            if ($this->notes->isEmpty()) {
                return 'planifiee';
            } else {
                return '';
            }
        }
    }

    public function getTypeGroupe(): ?TypeGroupeEnum
    {
        return $this->typeGroupe;
    }

    public function setTypeGroupe(?TypeGroupeEnum $type): ?self
    {
        $this->typeGroupe = $type;

        return $this;
    }

    #[Groups(['evaluation:detail'])]
    public function getTypeGroupeChoices(): array
    {
        return array_map(
            fn(TypeGroupeEnum $case): string => $case->value,
            TypeGroupeEnum::getTypes()
        );
    }
    #[Groups(['evaluation:detail'])]
    public function getTypeChoices(): array
    {
        return array_map(
            fn(TypeEvaluationEnum $case): string => $case->value,
            TypeEvaluationEnum::getTypes()
        );
    }
}
