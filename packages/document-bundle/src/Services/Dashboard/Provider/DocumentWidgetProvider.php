<?php

namespace DocumentBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;

class DocumentWidgetProvider implements WidgetProviderInterface
{
    public function getBundleCode(): string
    {
        return 'document';
    }

    public function getBundleLabel(): string
    {
        return 'Documents (GED)';
    }

    public function getWidgets(): array
    {
        return [
            new WidgetDefinition(
                'document.recents',
                'document',
                'Documents récents',
                'pi pi-file',
                'DocumentRecentsWidget',
                'medium',
                true,
                defaultConfig: ['position' => 3]
            ),
            new WidgetDefinition(
                'document.stats',
                'document',
                'Aperçu de la GED',
                'pi pi-folder',
                'DocumentStatsWidget',
                'small',
                true,
                allowedProfiles: [WidgetDefinition::PROFILE_PERSONNEL],
                requiredPermissions: ['ROLE_REFERENT', 'ROLE_REFERENT_INTRANET', 'ROLE_DIRECTION', 'ROLE_CHEF_DEPARTEMENT']
            ),
        ];
    }
}
