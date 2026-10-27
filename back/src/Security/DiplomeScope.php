<?php

namespace App\Security;

use App\Entity\Structure\StructureDiplome;

/** Resolves a unique diploma; shared or unknown resources deliberately have no scope. */
final class DiplomeScope
{
    public static function fromSubject(mixed $subject, int $depth = 0): ?StructureDiplome
    {
        if ($subject instanceof StructureDiplome) {
            return $subject;
        }
        if (!is_object($subject) || $depth > 6) {
            return null;
        }

        // Only follow the owning academic relationships, never a department's diploma list.
        foreach (['getDiplome', 'getSemestre', 'getPn', 'getAnnee', 'getEvaluation', 'getUe', 'getNiveau', 'getEnseignementUes', 'getEnseignement', 'getReferentiel'] as $getter) {
            if (is_callable([$subject, $getter])) {
                $owner = $subject->$getter();
                if ($owner instanceof \Doctrine\Common\Collections\Collection) {
                    if ($owner->count() !== 1) {
                        return null;
                    }
                    $owner = $owner->first();
                }
                $diplome = self::fromSubject($owner, $depth + 1);
                if ($diplome !== null) {
                    return $diplome;
                }
            }
        }
        return null;
    }
}
