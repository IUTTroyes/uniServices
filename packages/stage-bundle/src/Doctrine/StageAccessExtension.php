<?php

declare(strict_types=1);

namespace StageBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Doctrine\AbstractVisibiliteDepartementExtension;
use App\Entity\Users\Etudiant;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageSoutenance;
use Doctrine\ORM\QueryBuilder;

/** Un étudiant ne voit que ses stages ; le personnel, ceux de ses départements. */
final class StageAccessExtension extends AbstractVisibiliteDepartementExtension
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (!in_array($resourceClass, [StageEtudiant::class, StageAvenant::class, StageSoutenance::class], true) || $this->estSuperAdmin()) {
            return;
        }

        $user = $this->security->getUser();
        $root = $queryBuilder->getRootAliases()[0];
        if ($user instanceof Etudiant) {
            $studentStage = $resourceClass === StageEtudiant::class
                ? $root
                : $this->join($queryBuilder, $queryNameGenerator, $root, 'stageEtudiant');
            $owner = $queryNameGenerator->generateParameterName('stage_owner');
            $queryBuilder->andWhere(sprintf('%s.etudiant = :%s', $studentStage, $owner))->setParameter($owner, $user);

            return;
        }

        $periodOwner = $resourceClass === StageAvenant::class
            ? $this->join($queryBuilder, $queryNameGenerator, $root, 'stageEtudiant')
            : $root;
        $owner = $periodOwner;
        foreach (['stagePeriode', 'semestreProgramme', 'annee', 'pn', 'diplome'] as $field) {
            $owner = $this->join($queryBuilder, $queryNameGenerator, $owner, $field);
        }
        $this->limiterAuxDepartements($queryBuilder, $queryNameGenerator, $owner, ['ROLE_STAGE_VIEW', 'ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_SCOLARITE']);
    }
}
