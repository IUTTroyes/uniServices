<?php

namespace FinanceBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Users\Personnel;
use Doctrine\ORM\QueryBuilder;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReception;
use FinanceBundle\Security\FinanceAccess;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Listes de l'API restreintes à ce que l'utilisateur a le droit de voir (cf. FinanceAccess::peutVoir) :
 *  - super administrateur : tout ;
 *  - personnel : demandes des départements où il est gestionnaire ou valideur, demandes dont il est le demandeur
 *    ou le responsable désigné ; service financier (gestionnaire quelque part) : en plus, les demandes imputées à un service ;
 *  - autres : rien.
 * GET /api/finance_receptions suit la visibilité de la demande.
 */
final class FinanceVisibiliteExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly FinanceAccess $access,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== FinanceBonCommande::class && $resourceClass !== FinanceReception::class) {
            return;
        }
        $user = $this->security->getUser();
        if ($this->access->estSuperAdmin($user) || $this->access->estGestionnaireQuelquePart($user)) {
            return; // super administrateur ou service financier : toutes les demandes de l'établissement
        }
        if (!$user instanceof Personnel) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        if ($resourceClass === FinanceReception::class) {
            $commande = $queryNameGenerator->generateJoinAlias('bonCommande');
            $queryBuilder->innerJoin(sprintf('%s.bonCommande', $alias), $commande);
            $alias = $commande;
        }

        $utilisateur = $queryNameGenerator->generateParameterName('financeUser');
        $conditions = [
            sprintf('%s.demandeur = :%s', $alias, $utilisateur),
            sprintf('%s.responsable = :%s', $alias, $utilisateur),
        ];
        $queryBuilder->setParameter($utilisateur, $user);

        $departements = $this->access->departementsVisibles($user);
        if ($departements !== []) {
            $param = $queryNameGenerator->generateParameterName('financeDepartements');
            $conditions[] = sprintf('IDENTITY(%s.departement) IN (:%s)', $alias, $param);
            $queryBuilder->setParameter($param, $departements);
        }

        $queryBuilder->andWhere('('.implode(' OR ', $conditions).')');
    }
}
