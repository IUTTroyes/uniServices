<?php

namespace App\Entity\Dashboard;

use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use App\Repository\Dashboard\DashboardPreferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DashboardPreferenceRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_dashboard_preference_user_dashboard_widget_structure', columns: ['personnel_id', 'dashboard_code', 'widget_key', 'structure_departement_personnel_id'])]
class DashboardPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?StructureDepartementPersonnel $structureDepartementPersonnel = null;

    #[ORM\Column(length: 120)]
    private string $widgetKey;

    #[ORM\Column(length: 60, options: ['default' => 'intranet'])]
    private string $dashboardCode = 'intranet';

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column]
    private bool $collapsed = false;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(length: 20)]
    private string $size = 'medium';

    #[ORM\Column(type: Types::JSON)]
    private array $config = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getWidgetKey(): string
    {
        return $this->widgetKey;
    }

    public function getDashboardCode(): string
    {
        return $this->dashboardCode;
    }

    public function getStructureDepartementPersonnel(): ?StructureDepartementPersonnel
    {
        return $this->structureDepartementPersonnel;
    }

    public function setStructureDepartementPersonnel(?StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        $this->structureDepartementPersonnel = $structureDepartementPersonnel;

        return $this;
    }

    public function setWidgetKey(string $widgetKey): static
    {
        $this->widgetKey = $widgetKey;

        return $this;
    }

    public function setDashboardCode(string $dashboardCode): static
    {
        $this->dashboardCode = $dashboardCode;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

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
