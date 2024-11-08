<?php
/*
 * Copyright (c) 2021. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/htdocs/intranetV3/src/Event/JustificatifEvent.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 06/06/2021 09:46
 */

namespace App\Event;

use App\Entity\AbsenceJustificatif;
use Symfony\Contracts\EventDispatcher\Event;

class JustificatifEvent extends Event
{
    final public const DECISION = 'justificatif.decision';
    final public const ADDED = 'justificatif.added';
    final public const DELETED = 'justificatif.deleted';
    final public const DECISION_JUSTIFICATIF_ACCEPTEE = 'decision.justificatif.acceptee';
    final public const DECISION_JUSTIFICATIF_REFUSEE = 'decision.justificatif.refusee';

    public function __construct(protected AbsenceJustificatif $absenceJustificatif)
    {
    }

    public function getAbsenceJustificatif(): AbsenceJustificatif
    {
        return $this->absenceJustificatif;
    }
}
