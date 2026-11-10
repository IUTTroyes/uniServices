<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Scolarite\ScolEvaluationRattrapage;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Doctrine\ORM\QueryBuilder;
use IntranetBundle\Entity\Etudiant\MoyenneEnseignement;
use IntranetBundle\Entity\Etudiant\MoyenneUe;
use IntranetBundle\Entity\Previsionnel\Previsionnel;
use Symfony\Bundle\SecurityBundle\Security;

final class AcademicResultAccessExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
        if (!in_array($resource, [MoyenneEnseignement::class, MoyenneUe::class, ScolEvaluationRattrapage::class], true)) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof Personnel && $this->permissions->isSuperAdmin($user)) {
            return;
        }

        $root = $query->getRootAliases()[0];
        $retake = $resource === ScolEvaluationRattrapage::class;
        $semester = $retake
            ? $this->join($query, $names, $root, 'evaluation')
            : $this->join($query, $names, $root, 'etudiantScolariteSemestre');
        $record = $retake ? $root : $this->join($query, $names, $semester, 'scolarite');
        $account = $names->generateParameterName('result_account');
        if ($user instanceof Etudiant) {
            $query->andWhere(sprintf('%s.etudiant = :%s', $record, $account))->setParameter($account, $user);
            return;
        }

        if (!$user instanceof Personnel) {
            $query->andWhere('1 = 0');
            return;
        }

        $criteria = $query->expr()->orX(sprintf('%s.personnel = :%s', $root, $account));
        if ($retake) {
            $criteria->add(sprintf(':%s MEMBER OF %s.personnelAutorise', $account, $semester));
        }

        // Le prévisionnel doit correspondre à l’année du résultat.
        $planned = $names->generateJoinAlias('previsionnel_result');
        $teaching = $names->generateJoinAlias('teaching_result');
        $assignment = $query->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Previsionnel::class, $planned)
            ->where(sprintf('%s.personnel = :%s', $planned, $account));
        if ($resource === MoyenneUe::class) {
            $link = $names->generateJoinAlias('ue_result');
            $assignment->innerJoin($planned.'.enseignement', $teaching)
                ->innerJoin($teaching.'.enseignementUes', $link)
                ->andWhere(sprintf('%s.ue = %s.ue', $link, $root));
        } else {
            $assignment->andWhere(sprintf('%s.enseignement = %s.enseignement', $planned, $retake ? $semester : $root));
        }
        $assignment->andWhere(sprintf(
            '%s.anneeUniversitaire = %s.anneeUniversitaire', $planned, $retake ? $semester : $record,
        ));
        $criteria->add($query->expr()->exists($assignment->getDQL()));
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
            $owner = $record;
            if ($retake) {
                $owner = $this->join($query, $names, $semester, 'semestre');
                foreach (['annee', 'pn', 'diplome'] as $field) {
                    $owner = $this->join($query, $names, $owner, $field);
                }
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
