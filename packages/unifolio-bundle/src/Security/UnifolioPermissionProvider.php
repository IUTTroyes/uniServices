<?php

namespace UnifolioBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

class UnifolioPermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('unifolio.referent', UnifolioRoles::REFERENT, 'Référent Portfolio', 'unifolio', []),
            new PermissionDefinition('unifolio.corrector', UnifolioRoles::CORRECTEUR, 'Correcteur de portfolios', 'unifolio', []),
            new PermissionDefinition('unifolio.publisher', UnifolioRoles::PUBLICATION, 'Responsable publication', 'unifolio', []),
        ];
    }
}
