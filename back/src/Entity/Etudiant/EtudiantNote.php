<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Traits\LifeCycleTrait;
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
        new Get(
            uriTemplate: '/mini/etudiant_notes/{id}',
            normalizationContext: ['groups' => ['note:light']],
        ),
        new Get(
            uriTemplate: '/maxi/etudiant_notes/{id}',
            normalizationContext: ['groups' => ['note:detail', 'evaluation:detail']],
        ),
        new GetCollection(normalizationContext: ['groups' => ['note:detail']]),
        new GetCollection(
            uriTemplate: '/mini/etudiant_notes',
            normalizationContext: ['groups' => ['note:light']],
        ),
        new GetCollection(
            uriTemplate: '/maxi/etudiant_notes',
            normalizationContext: ['groups' => ['note:detail', 'evaluation:detail']],
        ),
        new Post(normalizationContext: ['groups' => ['note:write']], securityPostDenormalize: "is_granted('CAN_EDIT_NOTES', object)", processor: 'App\\DataProvider\\Evaluation\\EtudiantNotePersistProcessor'),
        new Patch(normalizationContext: ['groups' => ['note:write']], securityPostDenormalize: "is_granted('CAN_EDIT_NOTES', object)", processor: 'App\\DataProvider\\Evaluation\\EtudiantNotePersistProcessor'),
    ],
)]
#[ORM\HasLifecycleCallbacks]
class EtudiantNote
{
    public const STATUT_PRESENT = 'present';
    public const STATUT_ABSENT_JUSTIFIE = 'absent_justifie';
    public const STATUT_DISPENSE = 'dispense';
    public const STATUT_ABSENT_INJUSTIFIE = 'absent_injustifie';

    use LifeCycleTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['note:detail', 'evaluation:detail', 'note:light'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['note:write', 'note:detail', 'evaluation:detail'])]
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

    #[ORM\Column(length: 32)]
    #[Groups(['note:write', 'note:detail'])]
    private ?string $presenceStatut = self::STATUT_PRESENT;

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
        switch ($this->presenceStatut) {
            case self::STATUT_ABSENT_JUSTIFIE:
            case self::STATUT_DISPENSE:
                return -0.01;
            case self::STATUT_ABSENT_INJUSTIFIE:
                return 0.0;
            case self::STATUT_PRESENT:
            default:
                return $this->note;
        }
    }

    public function setNote(float $note): static
    {
        if ($note < -0.01 || $note > 20.0) {
            throw new \InvalidArgumentException('La valeur doit être comprise entre 0 et 20.');
        }

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

    // Nouveaux accesseurs pour le statut de présence
    public function getPresenceStatut(): ?string
    {
        return $this->presenceStatut;
    }

    public function setPresenceStatut(string $presenceStatut): static
    {
        $allowed = [
            self::STATUT_PRESENT,
            self::STATUT_ABSENT_JUSTIFIE,
            self::STATUT_DISPENSE,
            self::STATUT_ABSENT_INJUSTIFIE,
        ];

        if (!in_array($presenceStatut, $allowed, true)) {
            throw new \InvalidArgumentException('Statut de présence invalide.');
        }

        $this->presenceStatut = $presenceStatut;

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
