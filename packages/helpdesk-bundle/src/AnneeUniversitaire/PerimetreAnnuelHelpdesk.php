<?php

declare(strict_types=1);

namespace HelpdeskBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

final class PerimetreAnnuelHelpdesk extends AbstractPerimetreAnnuelProvider
{
    public function independants(): array
    {
        return ['HelpdeskCategorie', 'HelpdeskTicket', 'HelpdeskMessage'];
    }

    public function espaceDeNoms(): string
    {
        return 'HelpdeskBundle';
    }
}
