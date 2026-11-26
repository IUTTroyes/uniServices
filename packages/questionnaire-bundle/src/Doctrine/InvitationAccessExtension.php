<?php

declare(strict_types=1);

namespace QuestionnaireBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Doctrine\AbstractVisibiliteDepartementExtension;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use Doctrine\ORM\QueryBuilder;

/** Les invitations ne sont visibles que dans les départements de la personne. */
final class InvitationAccessExtension extends AbstractVisibiliteDepartementExtension
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ($resourceClass !== QuestionnaireInvitation::class || $this->estSuperAdmin()) {
            return;
        }

        $questionnaire = $this->join($queryBuilder, $queryNameGenerator, $queryBuilder->getRootAliases()[0], 'questionnaire');
        $this->limiterAuxDepartements($queryBuilder, $queryNameGenerator, $questionnaire, ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_QUALITE']);
    }
}
