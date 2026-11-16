<?php

declare(strict_types=1);

namespace App\Entity\Edt;

use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureAnneeUniversitaire;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'UNIQ_EDT_CONTRAINTES_ANNEE_SEMESTRE', columns: ['annee_universitaire_id', 'semestre_id'])]
class EdtContraintesSemestre
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureSemestre $semestre;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureAnneeUniversitaire $anneeUniversitaire;

    public function getAnneeUniversitaire(): StructureAnneeUniversitaire { return $this->anneeUniversitaire; }
    public function setAnneeUniversitaire(StructureAnneeUniversitaire $annee): static { $this->anneeUniversitaire = $annee; return $this; }

    /** Contraintes indexées par date civile du lundi (AAAA-MM-JJ). */
    #[ORM\Column(type: 'json')]
    private array $contraintes = [];

    public function getId(): ?int { return $this->id; }
    public function getSemestre(): StructureSemestre { return $this->semestre; }
    public function setSemestre(StructureSemestre $semestre): static { $this->semestre = $semestre; return $this; }
    public function getContraintes(): array { return $this->contraintes; }
    public function setContraintes(array $contraintes): static { $this->contraintes = $contraintes; return $this; }
}
