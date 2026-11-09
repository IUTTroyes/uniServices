<?php

namespace UnifolioBundle\Service\Portfolio;

/** Un modèle de portfolio est commun à tous les étudiants : il ne peut référencer aucune trace ni média personnel. */
final class ContexteModele implements ContexteBlocs
{
    public function traceAppartient(string $uuid): bool
    {
        return false;
    }

    public function mediaAppartient(string $traceUuid, int $mediaId): bool
    {
        return false;
    }
}
