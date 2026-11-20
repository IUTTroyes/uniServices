<?php

declare(strict_types=1);

namespace ParcautoBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

final class ParcautoPermissionProvider implements PermissionProviderInterface
{
    public const DEMANDE = 'ROLE_PARCAUTO_DEMANDEUR';
    public const GESTION = 'ROLE_PARCAUTO_GESTION';

    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('parcauto.demande', self::DEMANDE, 'Demandeur ParcAuto', 'parcauto', defaultAccess: true),
            new PermissionDefinition('parcauto.gestion', self::GESTION, 'Gestionnaire du parc automobile', 'parcauto', [self::DEMANDE]),
        ];
    }
}
