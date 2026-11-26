<?php

declare(strict_types=1);

namespace ParcautoBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

/** Le parc automobile ne dépend d'aucune année universitaire. */
final class PerimetreAnnuelParcauto extends AbstractPerimetreAnnuelProvider
{
    public function independants(): array
    {
        return [
            \ParcautoBundle\Entity\EcheanceEntretien::class,
            \ParcautoBundle\Entity\Equipement::class,
            \ParcautoBundle\Entity\JournalVehicule::class,
            \ParcautoBundle\Entity\Reservation::class,
            \ParcautoBundle\Entity\ValeurReferentiel::class,
            \ParcautoBundle\Entity\Vehicule::class,
        ];
    }
}
