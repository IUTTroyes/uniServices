<?php

declare(strict_types=1);

namespace App\Extension;

/** Objet d'un bundle dont les droits locaux se déduisent d'un autre sujet (relation propriétaire). */
interface RattachementPerimetreInterface
{
    public function getSujetDePerimetre(): mixed;
}
