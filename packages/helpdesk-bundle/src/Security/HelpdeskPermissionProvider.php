<?php

namespace HelpdeskBundle\Security;

use App\Application\ApplicationCatalog;
use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

class HelpdeskPermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('helpdesk.agent', 'ROLE_HELPDESK_AGENT', 'Agent de support', 'helpdesk', []),
            new PermissionDefinition('helpdesk.admin', 'ROLE_HELPDESK_ADMIN', 'Administrateur ' . ApplicationCatalog::nom('helpdesk'), 'helpdesk', ['ROLE_HELPDESK_AGENT']),
        ];
    }
}
