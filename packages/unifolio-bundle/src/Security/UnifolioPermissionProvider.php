<?php

namespace UnifolioBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

class UnifolioPermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('unifolio.referent', 'ROLE_REFERENT_PORTFOLIO', 'Référent Portfolio', 'unifolio', []),
            new PermissionDefinition('unifolio.corrector', 'ROLE_PORTFOLIO_CORRECTOR', 'Correcteur de portfolios', 'unifolio', []),
            new PermissionDefinition('unifolio.publisher', 'ROLE_PORTFOLIO_PUBLISHER', 'Responsable publication', 'unifolio', []),
        ];
    }
}
