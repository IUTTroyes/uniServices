<?php

namespace IntranetBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Domain\Dashboard\DashboardContext;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use IntranetBundle\Service\Dashboard\IntranetScheduleService;

class IntranetWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly IntranetScheduleService $schedule,
    ) {}

    public function supports(string $code): bool
    {
        return str_starts_with($code, 'intranet.');
    }

    public function getData(string $code, Personnel|Etudiant $user, ?DashboardContext $context = null): array
    {
        return match ($code) {
            'intranet.emploi_du_temps' => $this->schedule->getToday($user),
            'intranet.actions_urgentes' => $context?->getDiplomeIds() !== null
                ? ['items' => [], 'diplomeIds' => $context->getDiplomeIds()]
                : [
                'items' => [
                    ['label' => '3 validations de stages en attente', 'priority' => 'high'],
                    ['label' => '2 conventions à signer aujourd\'hui', 'priority' => 'medium'],
                ],
            ],
            'intranet.notes' => [
                'items' => [
                    ['title' => 'Relancer alternants absents', 'done' => false],
                    ['title' => 'Préparer réunion pédagogique', 'done' => true],
                ],
            ],
            'intranet.listings_etudiants' => [
                'supportedFormats' => ['pdf', 'xlsx', 'csv'],
                'hasGotenberg' => true,
                'hasSpreadsheet' => true,
            ],
            default => [],
        };
    }

}
