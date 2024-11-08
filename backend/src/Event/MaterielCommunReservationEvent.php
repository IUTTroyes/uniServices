<?php
/*
 * Copyright (c) 2021. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/htdocs/intranetV3/src/Event/MaterielCommunReservationEvent.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 07/02/2021 10:51
 */

namespace App\Event;

use App\Entity\MaterielCommunPret;

class MaterielCommunReservationEvent
{
    final public const ADDED = 'reservation.added';
    final public const REMOVED = 'reservation.removed';

    public function __construct(protected MaterielCommunPret $materielCommunPret)
    {
    }

    public function getReservationMaterielCommun(): MaterielCommunPret
    {
        return $this->materielCommunPret;
    }
}
