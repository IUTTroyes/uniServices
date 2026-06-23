<?php

namespace IntranetBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;

class IntranetWidgetProvider implements WidgetProviderInterface
{
    public function getBundleCode(): string
    {
        return 'intranet';
    }

    public function getBundleLabel(): string
    {
        return 'Intranet';
    }

    public function getWidgets(): array
    {
        return [
            new WidgetDefinition('intranet.emploi_du_temps', 'intranet', 'Aujourd\'hui', 'pi pi-calendar', 'EmploiDuTempsWidget', 'large', true),
            new WidgetDefinition('intranet.actions_urgentes', 'intranet', 'Actions urgentes', 'pi pi-sparkles', 'ActionsUrgentesWidget', 'medium', true),
            new WidgetDefinition('intranet.documents_recents', 'intranet', 'Documents récents', 'pi pi-file', 'DocumentsRecentsWidget', 'medium', true),
            new WidgetDefinition('intranet.notes', 'intranet', 'Notes', 'pi pi-pencil', 'NotesWidget', 'small', true),
        ];
    }
}
