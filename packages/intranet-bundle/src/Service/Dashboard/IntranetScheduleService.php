<?php

namespace IntranetBundle\Service\Dashboard;

use App\Entity\Users\Personnel;
use App\Entity\Users\Etudiant;
use App\Repository\Edt\EdtEventRepository;

class IntranetScheduleService
{
    public function __construct(private readonly EdtEventRepository $edtEventRepository) {}

    public function getToday(Personnel|Etudiant $user): array
    {
        if (!$user instanceof Personnel) {
            return [
                'todayLabel' => (new \IntlDateFormatter('fr_FR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, \IntlDateFormatter::GREGORIAN, 'EEEE d MMMM yyyy'))->format(new \DateTimeImmutable('today')),
                'items' => [],
            ];
        }

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
