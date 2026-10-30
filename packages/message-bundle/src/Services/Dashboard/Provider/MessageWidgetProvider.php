<?php

namespace MessageBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;

class MessageWidgetProvider implements WidgetProviderInterface
{
    public function getBundleCode(): string
    {
        return 'message';
    }

    public function getBundleLabel(): string
    {
        return 'Messagerie';
    }

    public function getWidgets(): array
    {
        return [
            new WidgetDefinition(
                'message.recent',
                'message',
                'Derniers messages',
                'pi pi-inbox',
                'MessageWidget',
                'medium',
                allowedProfiles: [WidgetDefinition::PROFILE_ETUDIANT, WidgetDefinition::PROFILE_PERSONNEL]
            ),
        ];
    }
}

