<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureUe;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EnseignementFilter::class)]
class EnseignementFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('semestre' !== $property || null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('semestre' === $property) {
                $queryBuilder
                    ->join("$alias.scolEnseignementUes", "scolEnseignementUe")
                    ->join("scolEnseignementUe.ue", "ue")
                    ->andWhere("ue.semestre = :semestre")
                    ->setParameter("semestre", $value);
            ;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'semestre' => [
                'property' => 'semestre',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
        ];
    }
}
