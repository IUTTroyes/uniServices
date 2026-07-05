<?php

namespace QuestionnaireBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

class QuestionnairePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('questionnaires.manage', 'ROLE_QUESTIONNAIRE_MANAGER', 'Administrateur Questionnaires', 'questionnaire', []),
        ];
    }
}
