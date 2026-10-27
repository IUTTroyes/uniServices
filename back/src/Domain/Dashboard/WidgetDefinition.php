<?php

namespace App\Domain\Dashboard;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;

class WidgetDefinition
{
    public const PROFILE_PERSONNEL = 'personnel';
    public const PROFILE_ETUDIANT = 'etudiant';

    public function __construct(
        private readonly string $code,
        private readonly string $bundle,
        private readonly string $label,
        private readonly string $icon,
        private readonly string $component,
        private readonly string $size = 'medium',
        private readonly bool $enabled = true,
        private readonly array $defaultConfig = [],
        private readonly array $allowedProfiles = [self::PROFILE_PERSONNEL, self::PROFILE_ETUDIANT],
        private readonly array $requiredPermissions = [],
        private readonly bool $requireAllPermissions = false,
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getBundle(): string
    {
        return $this->bundle;
    }

    public function isAllowedForUser(Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return in_array(self::PROFILE_PERSONNEL, $this->allowedProfiles, true);
        }

        return in_array(self::PROFILE_ETUDIANT, $this->allowedProfiles, true);
    }

    /** @return string[] */
    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    public function requiresAllPermissions(): bool
    {
        return $this->requireAllPermissions;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'bundle' => $this->bundle,
            'label' => $this->label,
            'icon' => $this->icon,
            'component' => $this->component,
            'size' => $this->size,
            'enabled' => $this->enabled,
            'defaultConfig' => $this->defaultConfig,
            'allowedProfiles' => $this->allowedProfiles,
            'requiredPermissions' => $this->requiredPermissions,
            'requireAllPermissions' => $this->requireAllPermissions,
        ];
    }
}
