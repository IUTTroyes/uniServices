<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use App\Entity\Traits\LifeCycleTrait;
use App\Repository\ExternalToolConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ExternalToolConfigRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('IS_SUPER_ADMIN')"),
        new Get(security: "is_granted('IS_SUPER_ADMIN')"),
        new Put(security: "is_granted('IS_SUPER_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['tool_config:read']],
    denormalizationContext: ['groups' => ['tool_config:write']],
)]class ExternalToolConfig
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tool_config:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['tool_config:read', 'tool_config:write'])]
    private ?string $tool_key = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tool_config:read', 'tool_config:write'])]
    private ?string $libelle = null;

    #[ORM\Column]
    #[Groups(['tool_config:read', 'tool_config:write'])]
    private ?bool $actif = null;

    #[ORM\Column]
    #[Groups(['tool_config:read', 'tool_config:write'])]
    private array $config = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToolKey(): ?string
    {
        return $this->tool_key;
    }

    public function setToolKey(string $tool_key): static
    {
        $this->tool_key = $tool_key;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }
}
