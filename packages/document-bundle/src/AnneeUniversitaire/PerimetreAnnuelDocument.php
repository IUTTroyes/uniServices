<?php

declare(strict_types=1);

namespace DocumentBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

final class PerimetreAnnuelDocument extends AbstractPerimetreAnnuelProvider
{
    public function independants(): array
    {
        return ['Document', 'DocumentCategory'];
    }

    public function espaceDeNoms(): string
    {
        return 'DocumentBundle';
    }
}
