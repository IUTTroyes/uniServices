<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;

/** Les migrations du cœur et des packages s'enchaînent par date, quel que soit leur espace de noms. */
final class MigrationsComparator implements Comparator
{
    public function compare(Version $a, Version $b): int
    {
        $date = static fn (Version $v): string => substr((string) $v, (int) strrpos((string) $v, '\\') + 1);

        // À date égale, le cœur (« DoctrineMigrations ») passe avant les packages qui en dépendent.
        return [$date($a), !str_starts_with((string) $a, 'DoctrineMigrations'), (string) $a]
            <=> [$date($b), !str_starts_with((string) $b, 'DoctrineMigrations'), (string) $b];
    }
}
