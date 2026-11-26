<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/** Restreint une collection aux départements où la personne détient l'un des rôles attendus. */
abstract class AbstractVisibiliteDepartementExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        protected readonly Security $security,
        protected readonly UserEffectivePermissionService $permissions,
    ) {}

    protected function estSuperAdmin(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof Personnel && $this->permissions->isSuperAdmin($user);
    }

    /**
     * Les filtres client et la pagination ne doivent pas élargir le périmètre.
     *
     * @param list<string> $roles
     * @param string       $proprietaire alias portant la relation « departement »
     */
    protected function limiterAuxDepartements(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $names, string $proprietaire, array $roles): void
    {
        $user = $this->security->getUser();
        $departements = [];
        if ($user instanceof Personnel) {
            foreach ($user->getDepartementsPersonnel() as $affectation) {
                $departement = $affectation->getDepartement();
                if ($departement?->getId() !== null && $this->permissions->hasAnyPermissionInDepartment($user, $roles, $departement)) {
                    $departements[] = $departement->getId();
                }
            }
        }
        if ($departements === []) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $parametre = $names->generateParameterName('visible_departments');
        $queryBuilder->andWhere(sprintf('IDENTITY(%s.departement) IN (:%s)', $proprietaire, $parametre))
            ->setParameter($parametre, array_values(array_unique($departements)));
    }

    protected function join(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $names, string $owner, string $field): string
    {
        $alias = $names->generateJoinAlias($field);
        $queryBuilder->innerJoin($owner.'.'.$field, $alias);

        return $alias;
    }
}
