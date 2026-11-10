<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Doctrine\ORM\QueryBuilder;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReception;
use HelpdeskBundle\Entity\HelpdeskTicket;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageSoutenance;
use Symfony\Bundle\SecurityBundle\Security;

final class DepartmentCollectionAccessExtension implements QueryCollectionExtensionInterface
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
        $finance = in_array($resourceClass, [FinanceBonCommande::class, FinanceReception::class], true);
        $stage = in_array($resourceClass, [StageEtudiant::class, StageAvenant::class, StageSoutenance::class], true);
        $invitation = $resourceClass === QuestionnaireInvitation::class;
        $studentRecord = in_array($resourceClass, [EtudiantScolarite::class, EtudiantScolariteSemestre::class, EtudiantNote::class], true);
        if (!$finance && !$stage && !$invitation && !$studentRecord
            && !in_array($resourceClass, [HelpdeskTicket::class, Etudiant::class], true)) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof Personnel && $this->permissions->isSuperAdmin($user)) {
            return;
        }

        $root = $queryBuilder->getRootAliases()[0];
        if ($resourceClass === HelpdeskTicket::class) {
            if (!$user instanceof Personnel) {
                $queryBuilder->andWhere('1 = 0');
                return;
            }
            if (!$this->permissions->hasAnyPermission($user, ['ROLE_HELPDESK_AGENT', 'ROLE_HELPDESK_ADMIN'])) {
                $owner = $queryNameGenerator->generateParameterName('ticket_owner');
                $queryBuilder->andWhere(sprintf('(%s.auteur = :%s OR %s.assigne = :%s)', $root, $owner, $root, $owner))
                    ->setParameter($owner, $user);
            }
            return;
        }

        if ($resourceClass === Etudiant::class) {
            if ($user instanceof Etudiant) {
                $parameter = $queryNameGenerator->generateParameterName('student_owner');
                $queryBuilder->andWhere(sprintf('%s.id = :%s', $root, $parameter))->setParameter($parameter, $user->getId());
            } elseif (!$user instanceof Personnel) {
                $queryBuilder->andWhere('1 = 0');
            }
            return;
        }

        if ($studentRecord) {
            $scolarite = match ($resourceClass) {
                EtudiantScolarite::class => $root,
                EtudiantScolariteSemestre::class => $this->join($queryBuilder, $queryNameGenerator, $root, 'scolarite'),
                EtudiantNote::class => $this->join(
                    $queryBuilder, $queryNameGenerator,
                    $this->join($queryBuilder, $queryNameGenerator, $root, 'scolariteSemestre'), 'scolarite',
                ),
            };
            if ($user instanceof Etudiant) {
                $parameter = $queryNameGenerator->generateParameterName('student_owner');
                $queryBuilder->andWhere(sprintf('%s.etudiant = :%s', $scolarite, $parameter))->setParameter($parameter, $user);
                if ($resourceClass === EtudiantNote::class) {
                    $evaluation = $this->join($queryBuilder, $queryNameGenerator, $root, 'evaluation');
                    $queryBuilder->andWhere($evaluation.'.visible = true');
                }
                return;
            }
        }

        if ($stage && $user instanceof Etudiant) {
            $studentStage = $resourceClass === StageEtudiant::class
                ? $root
                : $this->join($queryBuilder, $queryNameGenerator, $root, 'stageEtudiant');
            $owner = $queryNameGenerator->generateParameterName('stage_owner');
            $queryBuilder->andWhere(sprintf('%s.etudiant = :%s', $studentStage, $owner))->setParameter($owner, $user);
            return;
        }

        $roles = match (true) {
            $finance => ['ROLE_FINANCE_VIEW'],
            $invitation => ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_QUALITE'],
            $resourceClass === EtudiantNote::class => ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_NOTES_MANAGER', 'ROLE_PERMANENT'],
            $studentRecord => ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_SCOLARITE', 'ROLE_ASSISTANT', 'ROLE_DIRECTEUR_ETUDES', 'ROLE_PERMANENT'],
            default => ['ROLE_STAGE_VIEW', 'ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_SCOLARITE'],
        };
        $departmentIds = [];
        if ($user instanceof Personnel) {
            foreach ($user->getDepartementsPersonnel() as $assignment) {
                $department = $assignment->getDepartement();
                if ($department?->getId() !== null && $this->permissions->hasAnyPermissionInDepartment($user, $roles, $department)) {
                    $departmentIds[] = $department->getId();
                }
            }
        }
        if ($departmentIds === []) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        // Les filtres client et la pagination ne doivent pas élargir le périmètre.
        if ($studentRecord) {
            $owner = $scolarite;
        } elseif ($invitation) {
            $owner = $this->join($queryBuilder, $queryNameGenerator, $root, 'questionnaire');
        } elseif ($finance) {
            $owner = $resourceClass === FinanceReception::class
                ? $this->join($queryBuilder, $queryNameGenerator, $root, 'bonCommande')
                : $root;
        } else {
            $periodOwner = $resourceClass === StageAvenant::class
                ? $this->join($queryBuilder, $queryNameGenerator, $root, 'stageEtudiant')
                : $root;
            $period = $this->join($queryBuilder, $queryNameGenerator, $periodOwner, 'stagePeriode');
            $semester = $this->join($queryBuilder, $queryNameGenerator, $period, 'semestreProgramme');
            $year = $this->join($queryBuilder, $queryNameGenerator, $semester, 'annee');
            $program = $this->join($queryBuilder, $queryNameGenerator, $year, 'pn');
            $owner = $this->join($queryBuilder, $queryNameGenerator, $program, 'diplome');
        }

        $parameter = $queryNameGenerator->generateParameterName('visible_departments');
        $queryBuilder->andWhere(sprintf('IDENTITY(%s.departement) IN (:%s)', $owner, $parameter))
            ->setParameter($parameter, array_values(array_unique($departmentIds)));
    }

    private function join(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $names, string $owner, string $field): string
    {
        $alias = $names->generateJoinAlias($field);
        $queryBuilder->innerJoin($owner.'.'.$field, $alias);

        return $alias;
    }
}
