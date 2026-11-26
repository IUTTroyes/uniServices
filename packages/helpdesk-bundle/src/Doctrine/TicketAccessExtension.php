<?php

declare(strict_types=1);

namespace HelpdeskBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Doctrine\ORM\QueryBuilder;
use HelpdeskBundle\Entity\HelpdeskTicket;
use Symfony\Bundle\SecurityBundle\Security;

/** Hors agents et administrateurs, chacun ne voit que les tickets qu'il a ouverts ou qui lui sont assignés. */
final class TicketAccessExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UserEffectivePermissionService $permissions,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ($resourceClass !== HelpdeskTicket::class) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof Personnel) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }
        if ($this->permissions->isSuperAdmin($user)
            || $this->permissions->hasAnyPermission($user, ['ROLE_HELPDESK_AGENT', 'ROLE_HELPDESK_ADMIN'])) {
            return;
        }

        $root = $queryBuilder->getRootAliases()[0];
        $owner = $queryNameGenerator->generateParameterName('ticket_owner');
        $queryBuilder->andWhere(sprintf('(%s.auteur = :%s OR %s.assigne = :%s)', $root, $owner, $root, $owner))
            ->setParameter($owner, $user);
    }
}
