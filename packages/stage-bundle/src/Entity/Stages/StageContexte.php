<?php

namespace StageBundle\Entity\Stages;

use Doctrine\ORM\Mapping as ORM;
use StageBundle\Enum\TypeStageEnum;

#[ORM\Entity]
#[ORM\Table(name: 'stage_contexte')]
class StageContexte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: StageEtudiant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StageEtudiant $stageEtudiant = null;

    #[ORM\Column(length: 50, enumType: TypeStageEnum::class)]
    private TypeStageEnum $type = TypeStageEnum::CLASSIQUE;

    public function getId(): ?int { return $this->id; }
    public function getStageEtudiant(): ?StageEtudiant { return $this->stageEtudiant; }
    public function setStageEtudiant(StageEtudiant $stageEtudiant): static { $this->stageEtudiant = $stageEtudiant; return $this; }
    public function getType(): TypeStageEnum { return $this->type; }
    public function setType(TypeStageEnum $type): static { $this->type = $type; return $this; }
}
