<?php

declare(strict_types=1);

namespace App\Extension;

/** Entité normalement annuelle qui peut ne dépendre d'aucune année (un modèle, par exemple). */
interface HorsAnneeInterface
{
    public function estHorsAnnee(): bool;
}
