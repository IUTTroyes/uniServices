<?php

namespace StageBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use StageBundle\Doctrine\JointuresStageTrait;
use StageBundle\Util\Iri;

class StageEtudiantFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use JointuresStageTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (isset($context['parameter'])) {
            $parameter = $context['parameter'];
            $property = $parameter->getProperty() ?? $parameter->getKey();
            $this->filterProperty($property, $parameter->getValue(), $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);

            return;
        }

        foreach ($context['filters'] ?? [] as $property => $value) {
            $this->filterProperty((string) $property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ('tuteurUniversitaire' === $property) {
            $tutId = Iri::id($value);
            if ($tutId !== null) {
                $tutAlias = $this->jointure($queryBuilder, $queryNameGenerator, $alias, 'tuteurUniversitaire');
                $queryBuilder
                    ->andWhere(sprintf('%s.id = :stageTuteurUnivId', $tutAlias))
                    ->setParameter('stageTuteurUnivId', $tutId);
            }
        }

        if ('stagePeriode' === $property) {
            $periodeId = Iri::id($value);
            if ($periodeId !== null) {
                $queryBuilder
                    ->andWhere(sprintf('%s.stagePeriode = :stagePeriodeId', $alias))
                    ->setParameter('stagePeriodeId', $periodeId);
            }
        }

        if ('etudiant' === $property) {
            $etudiantId = Iri::id($value);
            if ($etudiantId !== null) {
                $queryBuilder
                    ->andWhere(sprintf('%s.etudiant = :stageEtudiantId', $alias))
                    ->setParameter('stageEtudiantId', $etudiantId);
            }
        }

        if ('anneeUniversitaire' === $property) {
            $univId = Iri::id($value);
            if ($univId !== null) {
                $spAlias = $this->jointure($queryBuilder, $queryNameGenerator, $alias, 'stagePeriode');
                $auAlias = $this->jointure($queryBuilder, $queryNameGenerator, $spAlias, 'anneeUniversitaire');
                $queryBuilder
                    ->andWhere(sprintf('(%s.id = :stageEtudiantAnneeUniv OR %s.annee = :stageEtudiantAnneeUniv)', $auAlias, $auAlias))
                    ->setParameter('stageEtudiantAnneeUniv', $univId);
            }
        }

        if ('departement' === $property) {
            $deptId = Iri::id($value);
            if ($deptId !== null) {
                $spAlias = $this->jointure($queryBuilder, $queryNameGenerator, $alias, 'stagePeriode');
                $spDiplome = $this->jointureDiplome($queryBuilder, $queryNameGenerator, $spAlias);
                $queryBuilder
                    ->andWhere(sprintf('%s.departement = :stageEtudiantDept', $spDiplome))
                    ->setParameter('stageEtudiantDept', $deptId);
            }
        }
    }
}
