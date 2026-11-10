<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Edt\EdtEvent;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\TypeEvenementEdt;
use App\Security\UserEffectivePermissionService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class EdtEventAccessExtension implements QueryCollectionExtensionInterface
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
        if ($resourceClass !== EdtEvent::class) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof Personnel && $this->permissions->isSuperAdmin($user)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $type = $queryNameGenerator->generateParameterName('edt_personal_type');
        $publicEvents = sprintf('%s.typeEvenement <> :%s', $alias, $type);

        if ($user instanceof Personnel) {
            $owner = $queryNameGenerator->generateParameterName('edt_personal_owner');
            $queryBuilder
                ->andWhere(sprintf('(%s OR %s.personnel = :%s)', $publicEvents, $alias, $owner))
                ->setParameter($type, TypeEvenementEdt::Personnel->value)
                ->setParameter($owner, $user);
            return;
        }

        // Filtrer avant le calcul des statistiques et indépendamment des filtres fournis par le client.
        if (!$user instanceof Etudiant || $user->getGroupes()->isEmpty()) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $parameter = $queryNameGenerator->generateParameterName('edt_access_groups');
        $groupIds = $user->getGroupes()->map(static fn ($group) => $group->getId())->toArray();

        $queryBuilder
            ->andWhere($publicEvents)
            ->setParameter($type, TypeEvenementEdt::Personnel->value)
            ->andWhere(sprintf('IDENTITY(%s.groupe) IN (:%s)', $alias, $parameter))
            ->setParameter($parameter, array_values($groupIds));
    }
}
