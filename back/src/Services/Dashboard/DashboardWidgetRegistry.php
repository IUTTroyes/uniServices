<?php

namespace App\Services\Dashboard;

use App\Domain\Dashboard\DashboardWidgetInterface;
use App\Services\Dashboard\Widget\ActionsUrgentesWidget;
use App\Services\Dashboard\Widget\DocumentsRecentsWidget;
use App\Services\Dashboard\Widget\EmploiDuTempsWidget;

class DashboardWidgetRegistry
{
    /**
     * @var array<string, DashboardWidgetInterface>
     */
    private array $widgets = [];

    public function __construct(
        EmploiDuTempsWidget $emploiDuTempsWidget,
        ActionsUrgentesWidget $actionsUrgentesWidget,
        DocumentsRecentsWidget $documentsRecentsWidget,
    ) {
        foreach ([$emploiDuTempsWidget, $actionsUrgentesWidget, $documentsRecentsWidget] as $widget) {
            $this->widgets[$widget->getKey()] = $widget;
        }
    }

    /**
     * @return DashboardWidgetInterface[]
     */
    public function all(): array
    {
        return array_values($this->widgets);
    }

    public function get(string $key): ?DashboardWidgetInterface
    {
        return $this->widgets[$key] ?? null;
    }
}
