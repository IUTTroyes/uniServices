<?php

namespace StageBundle\Entity\Stages;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Users\Personnel;
use StageBundle\Repository\Stages\StageSoutenanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StageSoutenanceRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['stage_soutenance:read']], security: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())"),
        // Liste restreinte aux départements gérés : StageVisibiliteExtension
        new GetCollection(normalizationContext: ['groups' => ['stage_soutenance:read']], security: "is_granted('IS_PERSONNEL')"),
        // La planification passe par StageSoutenanceController (contrôle des conflits + notifications).
        new Post(
            normalizationContext: ['groups' => ['stage_soutenance:read']],
            denormalizationContext: ['groups' => ['stage_soutenance:write']],
            securityPostDenormalize: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())"
        ),
        new Patch(
            normalizationContext: ['groups' => ['stage_soutenance:read']],
            denormalizationContext: ['groups' => ['stage_soutenance:write']],
            security: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())",
            securityPostDenormalize: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())"
        ),
        new Delete(security: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())")
    ],
    parameters: [
        'stagePeriode' => new QueryParameter(filter: SearchFilter::class),
    ]
)]
class StageSoutenance implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_soutenance:read', 'stage_etudiant:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StageEtudiant::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL', unique: true)]
    #[Groups(['stage_soutenance:read', 'stage_soutenance:write'])]
    private ?StageEtudiant $stageEtudiant = null;

    #[ORM\ManyToOne(targetEntity: StagePeriode::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['stage_soutenance:read', 'stage_soutenance:write'])]
    private ?StagePeriode $stagePeriode = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['stage_soutenance:read', 'stage_soutenance:write'])]
    private ?Personnel $enseignantJury = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['stage_soutenance:read', 'stage_soutenance:write'])]
    private ?\DateTimeInterface $dateSoutenance = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['stage_soutenance:read', 'stage_soutenance:write'])]
    private ?string $salle = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['stage_soutenance:read', 'stage_soutenance:write'])]
    private ?int $duree = 30; // in minutes

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStageEtudiant(): ?StageEtudiant
    {
        return $this->stageEtudiant;
    }

    public function setStageEtudiant(?StageEtudiant $stageEtudiant): self
    {
        $this->stageEtudiant = $stageEtudiant;

        return $this;
    }

    public function getEnseignantJury(): ?Personnel
    {
        return $this->enseignantJury;
    }

    public function setEnseignantJury(?Personnel $enseignantJury): self
    {
        $this->enseignantJury = $enseignantJury;

        return $this;
    }

    public function getDateSoutenance(): ?\DateTimeInterface
    {
        return $this->dateSoutenance;
    }

    public function setDateSoutenance(?\DateTimeInterface $dateSoutenance): self
    {
        $this->dateSoutenance = $dateSoutenance;

        return $this;
    }

    public function getSalle(): ?string
    {
        return $this->salle;
    }

    public function setSalle(?string $salle): self
    {
        $this->salle = $salle;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;

        return $this;
    }

    public function getStagePeriode(): ?StagePeriode
    {
        return $this->stagePeriode;
    }

    public function setStagePeriode(?StagePeriode $stagePeriode): self
    {
        $this->stagePeriode = $stagePeriode;

        return $this;
    }
}
