<?php

namespace IntranetBundle\Entity\PlanCours;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\LifeCycleTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use IntranetBundle\Repository\PlanCours\PlanCoursSequenceRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PlanCoursSequenceRepository::class)]
#[ORM\Table(name: 'plan_cours_sequence')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['plancours:read']]),
        new GetCollection(normalizationContext: ['groups' => ['plancours:read']]),
        new Post(
            normalizationContext: ['groups' => ['plancours:read']],
            denormalizationContext: ['groups' => ['plancours:write']]
        ),
        new Patch(
            normalizationContext: ['groups' => ['plancours:read']],
            denormalizationContext: ['groups' => ['plancours:write']]
        ),
        new Delete(),
    ],
    paginationEnabled: false
)]
class PlanCoursSequence
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['plancours:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlanCours::class, inversedBy: 'planCoursSequences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?PlanCours $planCours = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?int $ordre = 1;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $programme = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $nbHeures = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plancours:read', 'plancours:write'])]
    private ?string $commentaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlanCours(): ?PlanCours
    {
        return $this->planCours;
    }

    public function setPlanCours(?PlanCours $planCours): static
    {
        $this->planCours = $planCours;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getProgramme(): ?string
    {
        return $this->programme;
    }

    public function setProgramme(string $programme): static
    {
        $this->programme = $programme;

        return $this;
    }

    public function getNbHeures(): ?string
    {
        return $this->nbHeures;
    }

    public function setNbHeures(?string $nbHeures): static
    {
        $this->nbHeures = $nbHeures;

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
}
