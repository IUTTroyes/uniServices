<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Scolarite\ScolEvaluationRattrapage;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Service\Enseignement\AffectationsEnseignement;
use App\Security\UserEffectivePermissionService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class AcademicResultAccessExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UserEffectivePermissionService $permissions,
        private readonly AffectationsEnseignement $affectations,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restrict($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restrict($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    private function restrict(QueryBuilder $query, QueryNameGeneratorInterface $names, string $resource): void
    {
        if ($resource !== ScolEvaluationRattrapage::class) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof Personnel && $this->permissions->isSuperAdmin($user)) {
            return;
        }

        $root = $query->getRootAliases()[0];
        $evaluation = $this->join($query, $names, $root, 'evaluation');
        $account = $names->generateParameterName('result_account');
        if ($user instanceof Etudiant) {
            $query->andWhere(sprintf('%s.etudiant = :%s', $root, $account))->setParameter($account, $user);
            return;
        }

        if (!$user instanceof Personnel) {
            $query->andWhere('1 = 0');
            return;
        }

        $criteria = $query->expr()->orX(sprintf('%s.personnel = :%s', $root, $account));
        $criteria->add(sprintf(':%s MEMBER OF %s.personnelAutorise', $account, $evaluation));

        // L’affectation doit correspondre à l’année du résultat.
        $source = $this->affectations->source();
        if ($source !== null) {
            $criteria->add($query->expr()->exists($source->dqlAffectation(
                $names->generateJoinAlias('affectation_result'), $account,
                $evaluation.'.enseignement', $evaluation.'.anneeUniversitaire',
            )));
        }
        $query->setParameter($account, $user);

        $departments = [];
        foreach ($user->getDepartementsPersonnel() as $affectation) {
            $department = $affectation->getDepartement();
            if ($department?->getId() !== null && $this->permissions->hasAnyPermissionInDepartment(
                $user, ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_SCOLARITE', 'ROLE_NOTES_MANAGER'], $department,
            )) {
                $departments[] = $department->getId();
            }
        }
        if ($departments !== []) {
            $owner = $this->join($query, $names, $evaluation, 'semestre');
            foreach (['annee', 'pn', 'diplome'] as $field) {
                $owner = $this->join($query, $names, $owner, $field);
            }
            $parameter = $names->generateParameterName('result_departments');
            $criteria->add(sprintf('IDENTITY(%s.departement) IN (:%s)', $owner, $parameter));
            $query->setParameter($parameter, $departments);
        }

        $query->andWhere($criteria);
    }

    private function join(QueryBuilder $query, QueryNameGeneratorInterface $names, string $owner, string $field): string
    {
        $alias = $names->generateJoinAlias($field);
        $query->leftJoin($owner.'.'.$field, $alias);

        return $alias;
    }
}
