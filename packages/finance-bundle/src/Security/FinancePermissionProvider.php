<?php

namespace FinanceBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

/** Rôles du package, attribués par département ; le gestionnaire (service financier) agit sur tout l'établissement (cf. FinanceAccess). */
class FinancePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('finance.view', FinanceAccess::ROLE_ACCES, 'Accéder aux finances et suivre ses demandes', 'finance', [], true),
            new PermissionDefinition('finance.demandeur', FinanceAccess::ROLE_DEMANDEUR, 'Créer des demandes d’achat', 'finance', [FinanceAccess::ROLE_ACCES]),
            new PermissionDefinition('finance.valideur', FinanceAccess::ROLE_VALIDEUR, 'Donner un avis sur les demandes d’achat du département', 'finance', [FinanceAccess::ROLE_ACCES]),
            new PermissionDefinition('finance.manager', FinanceAccess::ROLE_GESTIONNAIRE, 'Gestionnaire du Service Financier (toutes les demandes de l’établissement)', 'finance', [FinanceAccess::ROLE_ACCES, FinanceAccess::ROLE_DEMANDEUR]),
        ];
    }
}
