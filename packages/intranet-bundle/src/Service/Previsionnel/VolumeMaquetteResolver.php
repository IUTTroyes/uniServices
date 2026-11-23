<?php

declare(strict_types=1);

namespace IntranetBundle\Service\Previsionnel;

/**
 * Conservative reconciliation of V3 timetable hours:
 * - Keep an explicitly positive PN volume (current V4 behaviour).
 * - If PN is zero or missing, use the positive local IUT volume.
 * - Preserve a real zero if neither is positive.
 */
final class VolumeMaquetteResolver
{
    /** @param array{PN?: int|float|string|null, IUT?: int|float|string|null} $volumes */
    public static function resolve(array $volumes): float
    {
        $pn = (float) ($volumes['PN'] ?? 0);
        $iut = (float) ($volumes['IUT'] ?? 0);

        return $pn > 0.0 ? $pn : ($iut > 0.0 ? $iut : 0.0);
    }
}
