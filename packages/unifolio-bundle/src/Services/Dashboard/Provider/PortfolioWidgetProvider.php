<?php

namespace UnifolioBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;

class PortfolioWidgetProvider implements WidgetProviderInterface
{
    public function getBundleCode(): string
    {
        return 'portfolio';
    }

    public function getBundleLabel(): string
    {
        return 'Portfolio';
    }

    public function getWidgets(): array
    {
        return [
            new WidgetDefinition('portfolio.to_correct', 'portfolio', 'Éléments à corriger', 'pi pi-pencil', 'PortfolioToCorrectWidget', 'medium', true, allowedProfiles: [WidgetDefinition::PROFILE_PERSONNEL], requiredPermissions: ['ROLE_PORTFOLIO_CORRECTOR']),
            new WidgetDefinition('portfolio.progress', 'portfolio', 'Progression portfolio', 'pi pi-chart-line', 'PortfolioProgressWidget', 'small', true, allowedProfiles: [WidgetDefinition::PROFILE_ETUDIANT]),
            new WidgetDefinition('portfolio.alerts', 'portfolio', 'Alertes portfolio', 'pi pi-bell', 'PortfolioAlertsWidget', 'small', false, allowedProfiles: [WidgetDefinition::PROFILE_PERSONNEL], requiredPermissions: ['ROLE_REFERENT_PORTFOLIO', 'ROLE_PORTFOLIO_CORRECTOR', 'ROLE_PORTFOLIO_PUBLISHER']),
        ];
    }
}
