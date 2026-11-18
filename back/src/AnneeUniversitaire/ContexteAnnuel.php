<?php

declare(strict_types=1);

namespace App\AnneeUniversitaire;

/** Politique d’une opération HTTP ; ne remplace jamais ses contrôles d’accès. */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class ContexteAnnuel
{
    public const ANNUEL = 'annuel';
    public const INDEPENDANT = 'independant';
    public const TRANSVERSAL = 'transversal';
    public const RESSOURCE = 'ressource';

    public function __construct(public string $mode, public string $raison = '', public ?string $ressource = null, public string $cle = 'id', public string $identifiant = 'id')
    {
        if (!in_array($mode, [self::ANNUEL, self::INDEPENDANT, self::TRANSVERSAL, self::RESSOURCE], true)) throw new \InvalidArgumentException('Politique annuelle inconnue.');
    }
}
