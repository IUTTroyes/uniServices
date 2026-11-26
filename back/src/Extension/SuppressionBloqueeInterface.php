<?php

declare(strict_types=1);

namespace App\Extension;

/** Un bundle qui référence un objet du cœur peut en interdire la suppression. */
interface SuppressionBloqueeInterface
{
    public function bloque(object $objet): bool;
}
