<?php

namespace App\Services\Dashboard;

use App\Domain\Dashboard\DashboardWidgetInterface;
use App\Services\Dashboard\Widget\ActionsUrgentesWidget;
use App\Services\Dashboard\Widget\DocumentsRecentsWidget;
use App\Services\Dashboard\Widget\EmploiDuTempsWidget;
use App\Services\Dashboard\Widget\NotesWidget;

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
        NotesWidget $notesWidget,
    ) {
        foreach ([$emploiDuTempsWidget, $actionsUrgentesWidget, $documentsRecentsWidget, $notesWidget] as $widget) {
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
