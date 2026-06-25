<?php
final class PortalDashboardDefinition
{
    public function getCode(): string
    {
        return 'portal';
    }

    public function getWidgets(): array
    {
        return [
            new DashboardWidgetLayout(
                'intranet.emploi_du_temps',
                0,
                'large'
            ),

            new DashboardWidgetLayout(
                'portfolio.progress',
                1,
                'medium'
            ),

            new DashboardWidgetLayout(
                'questionnaire.pending',
                2,
                'medium'
            ),
        ];
    }
}
