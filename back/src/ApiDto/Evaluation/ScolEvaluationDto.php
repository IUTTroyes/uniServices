<?php

namespace App\ApiDto\Evaluation;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Enum\TypeEvaluationEnum;
use App\Enum\TypeGroupeEnum;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;

class ScolEvaluationDto
{
    // propriétés de base
    #[Groups(['evaluation:detail'])]
    protected string $libelle;
    #[Groups(['evaluation:detail'])]
    protected ?string $commentaire;
    #[Groups(['evaluation:detail'])]
    protected ?int $coeff = null;
    #[Groups(['evaluation:detail'])]
    protected ?\DateTimeInterface $date = null;
    #[Groups(['evaluation:detail'])]
    protected bool $visible = false;
    #[Groups(['evaluation:detail'])]
    protected bool $modifiable = false;
    #[Groups(['evaluation:detail'])]
    protected ?TypeEvaluationEnum $type = null;
    #[Groups(['evaluation:detail'])]
    protected ?Collection $personnelAutorise = null;
    #[Groups(['evaluation:detail'])]
    protected ?StructureAnneeUniversitaire $anneeUniversitaire = null;
    #[Groups(['evaluation:detail'])]
    protected ScolEnseignement $enseignement;
    #[Groups(['evaluation:detail'])]
    private ?StructureSemestre $semestre = null;
    #[Groups(['evaluation:detail'])]
    protected ?Collection $notes = null;
    #[Groups(['evaluation:detail'])]
    protected ?TypeGroupeEnum $typeGroupe = null;
    #[Groups(['evaluation:detail'])]
    protected string $etat = 'non_initialisee';

    // propriétés supplémentaires
    #[Groups(['evaluation:detail'])]
    protected ?float $completion = 0;

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): void
    {
        $this->libelle = $libelle;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): void
    {
        $this->commentaire = $commentaire;
    }

    public function getCoeff(): ?int
    {
        return $this->coeff;
    }

    public function setCoeff(?int $coeff): void
    {
        $this->coeff = $coeff;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
    }

    public function isModifiable(): bool
    {
        return $this->modifiable;
    }

    public function setModifiable(bool $modifiable): void
    {
        $this->modifiable = $modifiable;
    }

    public function getType(): ?TypeEvaluationEnum
    {
        return $this->type;
    }

    public function setType(?TypeEvaluationEnum $type): void
    {
        $this->type = $type;
    }

    public function getPersonnelAutorise(): ?Collection
    {
        return $this->personnelAutorise;
    }

    public function setPersonnelAutorise(?Collection $personnelAutorise): void
    {
        $this->personnelAutorise = $personnelAutorise;
    }

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): void
    {
        $this->anneeUniversitaire = $anneeUniversitaire;
    }

    public function getEnseignement(): ScolEnseignement
    {
        return $this->enseignement;
    }

    public function setEnseignement(ScolEnseignement $enseignement): void
    {
        $this->enseignement = $enseignement;
    }

    public function getSemestre(): ?StructureSemestre
    {
        return $this->semestre;
    }

    public function setSemestre(?StructureSemestre $semestre): void
    {
        $this->semestre = $semestre;
    }

    public function getNotes(): ?Collection
    {
        return $this->notes;
    }

    public function setNotes(?Collection $notes): void
    {
        $this->notes = $notes;
    }

    public function getTypeGroupe(): ?TypeGroupeEnum
    {
        return $this->typeGroupe;
    }

    public function setTypeGroupe(?TypeGroupeEnum $typeGroupe): void
    {
        $this->typeGroupe = $typeGroupe;
    }

    public function getEtat(): string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): void
    {
        $this->etat = $etat;
    }

    public function getCompletion(): ?float
    {
        return $this->completion;
    }

    public function setCompletion(?float $completion): void
    {
        $this->completion = $completion;
    }
}
