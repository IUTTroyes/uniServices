<?php

namespace StageBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

/** Jointures partagées par les filtres et extensions du package (réutilisation des jointures déjà présentes). */
trait JointuresStageTrait
{
    /** Alias de la jointure « $depuis.$association », créée (LEFT JOIN) si la requête ne l'a pas déjà. */
    private function jointure(QueryBuilder $qb, QueryNameGeneratorInterface $noms, string $depuis, string $association): string
    {
        foreach ($qb->getDQLPart('join') as $jointures) {
            foreach ($jointures as $jointure) {
                if ($jointure->getJoin() === "$depuis.$association") {
                    return (string) $jointure->getAlias();
                }
            }
        }
        $alias = $noms->generateJoinAlias($association);
        $qb->leftJoin("$depuis.$association", $alias);

        return $alias;
    }

    /** Alias du diplôme d'une période : période → semestre → année → PN → diplôme (département : « <alias>.departement »). */
    private function jointureDiplome(QueryBuilder $qb, QueryNameGeneratorInterface $noms, string $aliasPeriode): string
    {
        $alias = $aliasPeriode;
        foreach (['semestreProgramme', 'annee', 'pn', 'diplome'] as $association) {
            $alias = $this->jointure($qb, $noms, $alias, $association);
        }

        return $alias;
    }
}
