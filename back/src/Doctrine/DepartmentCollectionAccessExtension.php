<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\QueryBuilder;

/** Visibilité des étudiants et de leur scolarité. */
final class DepartmentCollectionAccessExtension extends AbstractVisibiliteDepartementExtension
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $studentRecord = in_array($resourceClass, [EtudiantScolarite::class, EtudiantScolariteSemestre::class, EtudiantNote::class], true);
        if ((!$studentRecord && $resourceClass !== Etudiant::class) || $this->estSuperAdmin()) {
            return;
        }

        $user = $this->security->getUser();
        $root = $queryBuilder->getRootAliases()[0];

        if ($resourceClass === Etudiant::class) {
            if ($user instanceof Etudiant) {
                $parameter = $queryNameGenerator->generateParameterName('student_owner');
                $queryBuilder->andWhere(sprintf('%s.id = :%s', $root, $parameter))->setParameter($parameter, $user->getId());
            } elseif (!$user instanceof Personnel) {
                $queryBuilder->andWhere('1 = 0');
            }

            return;
        }

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

        $this->limiterAuxDepartements($queryBuilder, $queryNameGenerator, $scolarite, $resourceClass === EtudiantNote::class
            ? ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_NOTES_MANAGER', 'ROLE_PERMANENT']
            : ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_SCOLARITE', 'ROLE_ASSISTANT', 'ROLE_DIRECTEUR_ETUDES', 'ROLE_PERMANENT']);
    }
}
