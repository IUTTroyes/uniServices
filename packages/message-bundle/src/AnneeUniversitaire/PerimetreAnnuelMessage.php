<?php

declare(strict_types=1);

namespace MessageBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

final class PerimetreAnnuelMessage extends AbstractPerimetreAnnuelProvider
{
    public function independants(): array
    {
        return ['Message', 'MessageDestinataire', 'MessagePieceJointe'];
    }

    public function espaceDeNoms(): string
    {
        return 'MessageBundle';
    }
}
