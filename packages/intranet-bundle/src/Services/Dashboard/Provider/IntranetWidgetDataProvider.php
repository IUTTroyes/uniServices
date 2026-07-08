<?php

namespace IntranetBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\Edt\EdtEventRepository;

class IntranetWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly EdtEventRepository $edtEventRepository,
    ) {}

    public function supports(string $code): bool
    {
        return str_starts_with($code, 'intranet.');
    }

    public function getData(string $code, Personnel $user): array
    {
        return match ($code) {
            'intranet.emploi_du_temps' => $this->getEmploiDuTemps($user),
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

    private function getEmploiDuTemps(Personnel $user): array
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');
        $events = $this->edtEventRepository->findByPersonnelAndRange($user->getId(), $today, $tomorrow);
        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, \IntlDateFormatter::GREGORIAN, 'EEEE d MMMM yyyy');
        return [
            'todayLabel' => $formatter->format($today),
            'items' => array_map(fn($e) => [
                'heure'  => $e->getDebut()?->format('H:i') . ' - ' . $e->getFin()?->format('H:i'),
                'groupe' => $e->getLibGroupe(),
                'cours'  => $e->getCodeModule() . ' - ' . $e->getLibModule(),
                'salle'  => $e->getSalle() ?? '-',
                'color'  => $e->getCouleur(),
                'eval'   => $e->isEvaluation(),
            ], $events),
        ];
    }
}
