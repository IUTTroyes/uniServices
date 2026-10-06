<?php

namespace FinanceBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

class FinancePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('finance.view', 'ROLE_FINANCE_VIEW', 'Consulter les bons de commande', 'finance', [], true),
            new PermissionDefinition('finance.demandeur', 'ROLE_FINANCE_DEMANDEUR', 'Créer des demandes d’achat', 'finance', ['ROLE_FINANCE_VIEW']),
            new PermissionDefinition('finance.manager', 'ROLE_FINANCE_MANAGER', 'Gestionnaire du Service Financier', 'finance', ['ROLE_FINANCE_VIEW', 'ROLE_FINANCE_DEMANDEUR']),
        ];
    }
}
