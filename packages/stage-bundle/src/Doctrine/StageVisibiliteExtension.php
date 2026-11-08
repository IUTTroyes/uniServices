<?php

namespace StageBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\QueryBuilder;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageSoutenance;
use StageBundle\Security\StageAccess;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Listes de l'API restreintes à ce que l'utilisateur a le droit de voir (cf. StageAccess) :
 *
 * GET /api/stage_etudiants
 *  - super administrateur : tout ;
 *  - personnel : dossiers des départements dont il gère les stages, dossiers dont il est tuteur universitaire,
 *    dossiers des périodes dont il est responsable ;
 *  - étudiant : les siens.
 *
 * GET /api/stage_soutenances : super administrateur, ou départements dont la personne gère les stages.
 */
final class StageVisibiliteExtension implements QueryCollectionExtensionInterface
{
    use JointuresStageTrait;

    public function __construct(
        private readonly Security $security,
        private readonly StageAccess $access,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== StageEtudiant::class && $resourceClass !== StageSoutenance::class) {
            return;
        }
        $user = $this->security->getUser();
        if ($this->access->estSuperAdmin($user)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ($user instanceof Etudiant && $resourceClass === StageEtudiant::class) {
            $param = $queryNameGenerator->generateParameterName('stageUser');
            $queryBuilder->andWhere(sprintf('%s.etudiant = :%s', $alias, $param))->setParameter($param, $user);

            return;
        }
        if (!$user instanceof Personnel) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $conditions = [];
        $periode = $this->jointure($queryBuilder, $queryNameGenerator, $alias, 'stagePeriode');

        $departements = $this->access->departementsGeres($user);
        if ($departements !== []) {
            $departement = sprintf('IDENTITY(%s.departement)', $this->jointureDiplome($queryBuilder, $queryNameGenerator, $periode));
            $param = $queryNameGenerator->generateParameterName('stageDepartements');
            $conditions[] = sprintf('%s IN (:%s)', $departement, $param);
            $queryBuilder->setParameter($param, $departements);
        }

        if ($resourceClass === StageEtudiant::class) {
            $param = $queryNameGenerator->generateParameterName('stageUser');
            $conditions[] = sprintf('%s.tuteurUniversitaire = :%s', $alias, $param);
            $conditions[] = sprintf('%s.responsablePrincipal = :%s', $periode, $param);
            $conditions[] = sprintf(':%s MEMBER OF %s.coResponsables', $param, $periode);
            $queryBuilder->setParameter($param, $user);
        }

        $queryBuilder->andWhere($conditions === [] ? '1 = 0' : '('.implode(' OR ', $conditions).')');
    }
}
