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
        ];
    }
}
