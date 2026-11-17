<?php

declare(strict_types=1);

namespace IntranetBundle\Commission\Service;

/** Autorise uniquement le service de validation à écrire les résultats arrêtés. */
final class CommissionWriteContext
{
    private int $depth = 0;
    public function isActive(): bool { return $this->depth > 0; }
    public function run(callable $operation): mixed
    {
        ++$this->depth;
        try { return $operation(); } finally { --$this->depth; }
    }
}
