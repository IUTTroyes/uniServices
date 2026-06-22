<?php

namespace StageBundle\Entity\Stages;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use StageBundle\Repository\Stages\StagePeriodeSoutenanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StagePeriodeSoutenanceRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['stage_periode:read']]),
        new GetCollection(normalizationContext: ['groups' => ['stage_periode:read']]),
        new Post(securityPostDenormalize: "is_granted('ROLE_STAGE')"),
        new Patch(securityPostDenormalize: "is_granted('ROLE_STAGE')"),
        new Delete(security: "is_granted('ROLE_STAGE')"),
    ]
)]
class StagePeriodeSoutenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_periode:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StagePeriode::class, inversedBy: 'periodesSoutenance')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StagePeriode $stagePeriode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['stage_periode:read'])]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['stage_periode:read'])]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['stage_periode:read'])]
    private ?\DateTimeInterface $dateRenduRapport = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_periode:read'])]
    private ?string $modalites = null;

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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDateRenduRapport(): ?\DateTimeInterface
    {
        return $this->dateRenduRapport;
    }

    public function setDateRenduRapport(\DateTimeInterface $dateRenduRapport): static
    {
        $this->dateRenduRapport = $dateRenduRapport;

        return $this;
    }

    public function getModalites(): ?string
    {
        return $this->modalites;
    }

    public function setModalites(?string $modalites): static
    {
        $this->modalites = $modalites;

        return $this;
    }
}
