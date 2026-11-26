<?php

declare(strict_types=1);

namespace App\Extension;

/**
 * Permet à un bundle d'ajouter des champs à la sérialisation d'un objet du cœur, sans que le cœur le connaisse.
 */
interface ChampsSerialisesInterface
{
    public function supporte(object $objet): bool;

    /**
     * @param list<string> $groupes groupes de sérialisation demandés
     *
     * @return array<string, mixed> nom du champ => valeur brute (normalisée ensuite avec les mêmes groupes)
     */
    public function champs(object $objet, array $groupes): array;
}
