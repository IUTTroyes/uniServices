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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use StageBundle\Repository\Stages\StageEvaluationCritereRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Critère d'évaluation du stagiaire par le tuteur en entreprise, propre à une période de stage
 * (noté de 1 « insuffisant » à 4 « très satisfaisant »). Défini par le responsable des stages du département.
 */
#[ORM\Entity(repositoryClass: StageEvaluationCritereRepository::class)]
#[ORM\Table(name: 'stage_evaluation_critere')]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('IS_PERSONNEL')"),
        new GetCollection(security: "is_granted('IS_PERSONNEL')"),
        new Post(securityPostDenormalize: "object.getStagePeriode() and is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())"),
        new Patch(
            security: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())",
            securityPostDenormalize: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())"
        ),
        new Delete(security: "is_granted('STAGE_PERIODE_MANAGE', object.getStagePeriode())"),
    ],
    normalizationContext: ['groups' => ['stage_critere:read']],
    denormalizationContext: ['groups' => ['stage_critere:write']],
    order: ['ordre' => 'ASC', 'id' => 'ASC'],
    parameters: [
        'stagePeriode' => new QueryParameter(filter: SearchFilter::class),
    ]
)]
class StageEvaluationCritere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_critere:read', 'stage_periode:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StagePeriode::class, inversedBy: 'criteresEvaluation')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['stage_critere:read', 'stage_critere:write'])]
    private ?StagePeriode $stagePeriode = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le libellé du critère est obligatoire.')]
    #[Assert\Length(max: 150)]
    #[Groups(['stage_critere:read', 'stage_critere:write', 'stage_periode:read'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_critere:read', 'stage_critere:write', 'stage_periode:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['stage_critere:read', 'stage_critere:write', 'stage_periode:read'])]
    private int $ordre = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStagePeriode(): ?StagePeriode
    {
        return $this->stagePeriode;
    }

    public function setStagePeriode(?StagePeriode $stagePeriode): static
    {
        $this->stagePeriode = $stagePeriode;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = trim($libelle);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description !== null && trim($description) !== '' ? trim($description) : null;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }
}
