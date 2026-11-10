<?php

namespace UnifolioBundle\Service\Portfolio;

/** Ce que le validateur doit savoir du propriétaire pour accepter des références à ses traces et médias. */
interface ContexteBlocs
{
    public function traceAppartient(string $uuid): bool;

    public function mediaAppartient(string $traceUuid, int $mediaId): bool;
}
