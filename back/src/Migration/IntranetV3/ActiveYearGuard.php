<?php

declare(strict_types=1);

namespace App\Migration\IntranetV3;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Keep Core's 2026-2027 operational boundary consistent with Stage's V3
 * active-year selection. Avoid silently mixing different source snapshots.
 */
final readonly class ActiveYearGuard
{
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.copy_connection')]
        private Connection $source,
    ) {
    }

    public function assertYear2026(): void
    {
        $years = array_map('intval', $this->source->fetchFirstColumn(
            'SELECT annee FROM annee_universitaire WHERE active = 1'
        ));
        if ($years !== [2026]) {
            throw new \DomainException(sprintf(
                'Le transfert opérationnel exige une seule année V3 active, 2026–2027 ; années actives trouvées : %s.',
                $years === [] ? 'aucune' : implode(', ', $years),
            ));
        }
    }
}
