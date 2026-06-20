<?php

namespace App\Domain\Dashboard;

use App\Entity\Users\Personnel;

interface DashboardWidgetInterface
{
    public function getKey(): string;

    public function getLabel(): string;

    public function getIcon(): string;

    public function getVueComponent(): string;

    public function supports(Personnel $user, DashboardContext $context): bool;

    public function getDefaultConfig(): array;

    public function getDefaultSize(): string;

    public function isDefaultEnabled(): bool;

    public function getDataUrl(): string;

    public function getData(Personnel $user, DashboardContext $context, array $config): array;
}
