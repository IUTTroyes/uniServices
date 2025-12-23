<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

#[ApiFilter(EtudiantNoteFilter::class)]
class EtudiantNoteFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('etudiant' === $property) {
            $queryBuilder
                ->leftJoin(sprintf('%s.scolariteSemestre', $alias), 'scolariteSemestre')
                ->leftJoin('scolariteSemestre.scolarite', 'scolarite')
                ->leftJoin('scolarite.etudiant', 'etudiant')
                ->andWhere('etudiant.id = :etudiant')
                ->setParameter('etudiant', $value);
        }

        if ('evaluation' === $property) {
            $queryBuilder
                ->andWhere(sprintf('%s.evaluation = :evaluation', $alias))
                ->setParameter('evaluation', $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'etudiant' => [
                'property' => 'etudiant',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by etudiant',
                ],
            ],
            'evaluation' => [
                'property' => 'evaluation',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by evaluation',
                ],
            ],
        ];
    }
}
