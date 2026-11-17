<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SemestreFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (isset($context['parameter'])) {
            $parameter = $context['parameter'];
            $this->filterProperty($parameter->getProperty() ?? $parameter->getKey(), $parameter->getValue(), $queryBuilder, $queryNameGenerator);
            return;
        }
        foreach ($context['filters'] ?? [] as $property => $value) {
            $this->filterProperty((string) $property, $value, $queryBuilder, $queryNameGenerator);
        }
    }

    private function filterProperty(string $property, mixed $value, QueryBuilder $qb, QueryNameGeneratorInterface $names): void
    {
        if ($value === null || !in_array($property, ['departement', 'diplome', 'annee', 'anneeUniversitaire'], true)) return;
        $root = $qb->getRootAliases()[0];
        $parameter = $names->generateParameterName($property);
        if ($property === 'annee') {
            $qb->andWhere("$root.annee = :$parameter")->setParameter($parameter, $value);
        } else {
            $annee = $this->join($qb, $names, $root, 'annee');
            $pn = $this->join($qb, $names, $annee, 'pn');
            if ($property === 'anneeUniversitaire') {
                $au = $this->join($qb, $names, $pn, 'anneeUniversitaire');
                if ($value === 'courante') $qb->andWhere("$au.actif = true");
                elseif (ctype_digit((string) $value) && (int) $value > 0) $qb->andWhere("$au.id = :$parameter")->setParameter($parameter, (int) $value);
                else throw new BadRequestHttpException('Année universitaire attendue : identifiant positif ou « courante ».');
            } else {
                $diplome = $this->join($qb, $names, $pn, 'diplome');
                $field = $property === 'departement' ? 'departement' : 'id';
                $qb->andWhere("$diplome.$field = :$parameter")->setParameter($parameter, $value);
            }
        }
        $qb->orderBy("$root.ordreLmd", 'ASC')->addOrderBy("$root.libelle", 'ASC');
    }

    private function join(QueryBuilder $qb, QueryNameGeneratorInterface $names, string $owner, string $field): string
    {
        foreach ($qb->getDQLPart('join') as $joins) {
            foreach ($joins as $join) if ($join->getJoin() === "$owner.$field") return $join->getAlias();
        }
        $alias = $names->generateJoinAlias($field);
        $qb->innerJoin("$owner.$field", $alias);
        return $alias;
    }
}
