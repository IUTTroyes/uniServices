<?php
final class IntranetDashboardDefintion
{
    public function getCode(): string
    {
        return 'intranet';
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
                'intranet.actions_urgentes',
                1,
                'medium'
            ),

            new DashboardWidgetLayout(
                'intranet.documents_recents',
                2,
                'medium'
            ),
            new DashboardWidgetLayout(
                'intranet.notes',
                3,
                'medium'
            ),
        ];
    }
}
