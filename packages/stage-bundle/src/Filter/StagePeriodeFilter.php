<?php

namespace StageBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use App\Entity\Users\Personnel;
use Doctrine\ORM\QueryBuilder;
use StageBundle\Doctrine\JointuresStageTrait;
use StageBundle\Util\Iri;
use StageBundle\Security\StageAccess;
use Symfony\Bundle\SecurityBundle\Security;

class StagePeriodeFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use JointuresStageTrait;

    public function __construct(
        private readonly Security $security,
        private readonly StageAccess $access,
    ) {
    }

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

        if ('departement' === $property) {
            $deptId = (int) Iri::id($value);
            $spDiplome = $this->jointureDiplome($queryBuilder, $queryNameGenerator, $alias);
            $queryBuilder
                ->andWhere(sprintf('%s.departement = :stageDept', $spDiplome))
                ->setParameter('stageDept', $deptId);
        }

        // geres=1 : périodes que l'utilisateur gère (départements où il est responsable des stages, ou périodes dont
        // il est responsable / co-responsable). Sans effet pour le super administrateur, qui gère tout.
        if ('geres' === $property && filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $user = $this->security->getUser();
            if ($this->access->estSuperAdmin($user)) {
                return;
            }
            if (!$user instanceof Personnel) {
                $queryBuilder->andWhere('1 = 0');

                return;
            }
            $conditions = [];
            $departements = $this->access->departementsGeres($user);
            if ($departements !== []) {
                $spDiplome = $this->jointureDiplome($queryBuilder, $queryNameGenerator, $alias);
                $deptParam = $queryNameGenerator->generateParameterName('stageDepartementsGeres');
                $conditions[] = sprintf('IDENTITY(%s.departement) IN (:%s)', $spDiplome, $deptParam);
                $queryBuilder->setParameter($deptParam, $departements);
            }
            $queryBuilder->andWhere($conditions === [] ? '1 = 0' : '('.implode(' OR ', $conditions).')');
        }

        if ('anneeUniversitaire' === $property) {
            $univId = (int) Iri::id($value);
            $spAu = $this->jointure($queryBuilder, $queryNameGenerator, $alias, 'anneeUniversitaire');
            $queryBuilder
                ->andWhere(sprintf('(%s.id = :stageAnneeUniv OR %s.annee = :stageAnneeUniv)', $spAu, $spAu))
                ->setParameter('stageAnneeUniv', $univId);
        }

        if ('actif' === $property) {
            $spAu = $this->jointure($queryBuilder, $queryNameGenerator, $alias, 'anneeUniversitaire');
            $queryBuilder
                ->andWhere(sprintf('%s.actif = :stageAuActif', $spAu))
                ->setParameter('stageAuActif', filter_var($value, FILTER_VALIDATE_BOOLEAN));
        }

        if ('annee' === $property || 'ordre' === $property || 'ordreAnnee' === $property) {
            $spSem = $this->jointure($queryBuilder, $queryNameGenerator, $alias, 'semestreProgramme');
            $spAnnee = $this->jointure($queryBuilder, $queryNameGenerator, $spSem, 'annee');
            if (is_numeric($value)) {
                $queryBuilder
                    ->andWhere(sprintf('%s.ordre = :stageAnneeOrdre', $spAnnee))
                    ->setParameter('stageAnneeOrdre', (int) $value);
            }
        }

        if ('semestre' === $property) {
            $semId = (int) Iri::id($value);
            $queryBuilder
                ->andWhere(sprintf('%s.semestreProgramme = :stageSemestre', $alias))
                ->setParameter('stageSemestre', $semId);
        }
    }
}
