<?php

namespace IntranetBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;

class IntranetWidgetDataProvider implements WidgetDataProviderInterface
{
    public function supports(string $code): bool
    {
        return str_starts_with($code, 'intranet.');
    }

    public function getData(string $code, Personnel $user): array
    {
        if ('intranet.emploi_du_temps' === $code) {
            return [
                'dataUrl' => '/api/widget/edt_events',
                'message' => 'Données alimentées par le provider EDT existant.',
            ];
        }

        return match ($code) {
            'intranet.actions_urgentes' => [
                'items' => [
                    ['label' => '3 validations de stages en attente', 'priority' => 'high'],
                    ['label' => '2 conventions à signer aujourd\'hui', 'priority' => 'medium'],
                ],
            ],
            'intranet.documents_recents' => [
                'items' => [
                    ['title' => 'PV Conseil de département', 'updatedAt' => '2026-06-21 09:15'],
                    ['title' => 'Planification jurys BUT2', 'updatedAt' => '2026-06-20 17:40'],
                ],
            ],
            'intranet.notes' => [
                'items' => [
                    ['title' => 'Relancer alternants absents', 'done' => false],
                    ['title' => 'Préparer réunion pédagogique', 'done' => true],
                ],
            ],
            default => [],
        };
    }
}
