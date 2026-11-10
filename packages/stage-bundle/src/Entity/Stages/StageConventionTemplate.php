<?php

namespace StageBundle\Entity\Stages;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use StageBundle\Repository\Stages\StageConventionTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StageConventionTemplateRepository::class)]
// Modèles communs à tous les départements : modification réservée au super administrateur.
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['stage_template:read']]),
        new GetCollection(normalizationContext: ['groups' => ['stage_template:read']]),
        new Post(securityPostDenormalize: "is_granted('IS_SUPER_ADMIN')", normalizationContext: ['groups' => ['stage_template:read']],
            denormalizationContext: ['groups' => ['stage_template:write']]),
        new Patch(securityPostDenormalize: "is_granted('IS_SUPER_ADMIN')", normalizationContext: ['groups' => ['stage_template:read']],
            denormalizationContext: ['groups' => ['stage_template:write']]),
        new Delete(security: "is_granted('IS_SUPER_ADMIN')"),
    ]
)]
class StageConventionTemplate
{
    /** Code du modèle de convention (un seul modèle actif) ; sans modèle en base, le gabarit Twig intégré est utilisé. */
    public const CODE_CONVENTION = 'default_convention';
    public const CODE_AVENANT = 'default_avenant';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_template:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['stage_template:read', 'stage_template:write'])]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Groups(['stage_template:read', 'stage_template:write'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['stage_template:read', 'stage_template:write'])]
    private ?string $texte = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): self
    {
        $this->texte = $texte;

        return $this;
    }
}
