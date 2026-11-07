<?php

namespace StageBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\QueryBuilder;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Security\StageAccess;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * GET /api/stage_etudiants : chacun ne liste que les dossiers qu'il a le droit de voir (cf. StageAccess).
 *  - gestionnaire : tout (les filtres de la requête s'appliquent ensuite) ;
 *  - personnel : dossiers dont il est tuteur universitaire, ou des périodes dont il est responsable ;
 *  - étudiant : les siens.
 */
final class StageEtudiantVisibiliteExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly StageAccess $access,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== StageEtudiant::class) {
            return;
        }
        $user = $this->security->getUser();
        if ($this->access->estGestionnaire($user)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $param = $queryNameGenerator->generateParameterName('stageUser');

        if ($user instanceof Etudiant) {
            $queryBuilder->andWhere(sprintf('%s.etudiant = :%s', $alias, $param))->setParameter($param, $user);

            return;
        }

        if ($user instanceof Personnel) {
            $periode = $queryNameGenerator->generateJoinAlias('stagePeriodeVisibilite');
            $queryBuilder
                ->leftJoin(sprintf('%s.stagePeriode', $alias), $periode)
                ->andWhere(sprintf(
                    '(%1$s.tuteurUniversitaire = :%2$s OR %3$s.responsablePrincipal = :%2$s OR :%2$s MEMBER OF %3$s.coResponsables)',
                    $alias, $param, $periode
                ))
                ->setParameter($param, $user);

            return;
        }

        $queryBuilder->andWhere('1 = 0');
    }
}
