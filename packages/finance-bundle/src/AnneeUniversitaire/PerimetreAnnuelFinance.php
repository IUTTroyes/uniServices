<?php

declare(strict_types=1);

namespace FinanceBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

final class PerimetreAnnuelFinance extends AbstractPerimetreAnnuelProvider
{
    public function independants(): array
    {
        return ['FinanceBonCommande', 'FinanceReception', 'FinanceReceptionDocument', 'FinanceFournisseur', 'FinanceTauxTva'];
    }

    public function espaceDeNoms(): string
    {
        return 'FinanceBundle';
    }
}
