<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Post;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use App\Filter\EtudiantNoteFilter;
use App\State\Processor\Evaluation\EtudiantNotePersistProcessor;
use App\Repository\EtudiantNoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


#[ORM\Entity(repositoryClass: EtudiantNoteRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_NOTE_EVALUATION_INSCRIPTION', columns: ['evaluation_id', 'scolarite_semestre_id'])]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('CAN_VIEW_NOTES', object)", normalizationContext: ['groups' => ['note:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['note:detail']]),
        new Post(
            normalizationContext: ['groups' => ['note:detail']],
            denormalizationContext: ['groups' => ['note:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_NOTES', object)",
            processor: EtudiantNotePersistProcessor::class
        ),
        new Patch(
            normalizationContext: ['groups' => ['note:detail']],
            denormalizationContext: ['groups' => ['note:write']],
            security: "is_granted('CAN_EDIT_NOTES', object)", securityPostDenormalize: "is_granted('CAN_EDIT_NOTES', object)",
            processor: EtudiantNotePersistProcessor::class
        ),
    ],
    parameters: [
        'etudiant' => new QueryParameter(filter: EtudiantNoteFilter::class),
        'evaluation' => new QueryParameter(filter: EtudiantNoteFilter::class),
    ]
)]
class EtudiantNote implements TimestampableInterface
{
    public const STATUT_PRESENT = 'present';
    public const STATUT_ABSENT_JUSTIFIE = 'absent_justifie';
    public const STATUT_DISPENSE = 'dispense';
    public const STATUT_ABSENT_INJUSTIFIE = 'absent_injustifie';

    use TimestampableTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['note:detail', 'evaluation:detail'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['note:write', 'note:detail', 'evaluation:detail'])]
    private ?float $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['note:write', 'note:detail'])]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['note:write', 'note:detail'])]
    private ?ScolEvaluation $evaluation = null;

    #[ORM\Column(length: 32)]
    #[Groups(['note:write', 'note:detail'])]
    private ?string $presenceStatut = self::STATUT_PRESENT;

    #[ORM\Column(nullable: true)]
    #[Groups(['note:detail'])]
    private ?array $historique = null;

    #[ORM\Column(nullable: false)]
    private ?bool $publiee = false;

    #[ORM\ManyToOne(inversedBy: 'note')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['note:write', 'note:detail'])]
    private ?EtudiantScolariteSemestre $scolariteSemestre = null;

    #[Groups(['note:write'])]
    private ?string $motifModification = null;

    public function getMotifModification(): ?string { return $this->motifModification; }
    public function setMotifModification(?string $motif): static { $this->motifModification = $motif; return $this; }

    public function __construct()
    {
        $this->setUuid();
    }

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

    #[Groups(['note:detail'])]
    public function getNoteBrute(): ?float { return $this->note; }

    #[Assert\Callback]
    public function validateRattachements(ExecutionContextInterface $context): void
    {
        $evaluation = $this->evaluation;
        $inscription = $this->scolariteSemestre;
        if ($evaluation === null || $inscription === null
            || $evaluation->getSemestre() !== $inscription->getSemestre()
            || $evaluation->getAnneeUniversitaire() !== $inscription->getScolarite()?->getAnneeUniversitaire()) {
            $context->buildViolation('La note doit relier une évaluation et une inscription du même semestre et de la même année universitaire.')
                ->atPath('scolariteSemestre')->addViolation();
        }
    }

    public function setNote(?float $note): static
    {
        if ($note !== null && ($note < -0.01 || $note > 20.0)) {
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

    public function isPubliee(): ?bool
    {
        return $this->publiee;
    }

    public function setPubliee(?bool $publiee): static
    {
        $this->publiee = $publiee;

        return $this;
    }
}
