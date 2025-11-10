<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Traits\UuidTrait;
use App\Filter\EtudiantNoteFilter;
use App\Repository\EtudiantNoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtudiantNoteRepository::class)]
#[ApiFilter(EtudiantNoteFilter::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['note:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['note:detail']]),
        new Post(normalizationContext: ['groups' => ['note:write']]),
        new Patch(normalizationContext: ['groups' => ['note:write']]),
        new Put(normalizationContext: ['groups' => ['note:write']]),
    ],
)]
class EtudiantNote
{
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['note:detail'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['note:write', 'note:detail'])]
    private ?float $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['note:write', 'note:detail'])]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[Groups(['note:write', 'note:detail'])]
    private ?ScolEvaluation $evaluation = null;

    #[ORM\ManyToOne()]
    #[Groups(['note:detail'])]
    private ?EtudiantScolarite $scolarite = null;

    #[ORM\Column]
    #[Groups(['note:write', 'note:detail'])]
    private ?bool $absenceJustifiee = null;

    #[ORM\Column(nullable: true)]
    private ?array $historique = null;

    #[ORM\ManyToOne(inversedBy: 'note')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['note:write', 'note:detail'])]
    private ?EtudiantScolariteSemestre $scolariteSemestre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(float $note): static
    {
        $this->note = $note;

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

    public function getEvaluation(): ?ScolEvaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?ScolEvaluation $evaluation): static
    {
        $this->evaluation = $evaluation;

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

    public function isAbsenceJustifiee(): ?bool
    {
        return $this->absenceJustifiee;
    }

    public function setAbsenceJustifiee(bool $absenceJustifiee): static
    {
        $this->absenceJustifiee = $absenceJustifiee;

        return $this;
    }

    public function getHistorique(): ?array
    {
        return $this->historique;
    }

    public function setHistorique(?array $historique): static
    {
        $this->historique = $historique;

        return $this;
    }

    public function getScolariteSemestre(): ?EtudiantScolariteSemestre
    {
        return $this->scolariteSemestre;
    }

    public function setScolariteSemestre(?EtudiantScolariteSemestre $scolariteSemestre): static
    {
        $this->scolariteSemestre = $scolariteSemestre;

        return $this;
    }
}
