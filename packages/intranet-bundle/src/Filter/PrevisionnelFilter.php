<?php

namespace IntranetBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Operation;
use App\Entity\Scolarite\ScolEnseignement;
use Doctrine\ORM\QueryBuilder;

#[ApiFilter(PrevisionnelFilter::class)]
class PrevisionnelFilter extends AbstractFilter
{
    private const FILTERS = [
        'personnel' => 'personnel',
        'anneeUniversitaire' => 'anneeUniversitaire',
        'departement' => 'departement',
        'annee' => 'annee',
        'semestre' => 'semestre',
        'enseignement' => 'enseignement',
        'diplome' => 'diplome',
    ];
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!isset(self::FILTERS[$property]) || null === $value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName($property);
        if (in_array($property, ['personnel', 'anneeUniversitaire', 'enseignement'], true)) {
            $queryBuilder->andWhere(sprintf('%s.%s = :%s', $alias, $property, $parameter))
                ->setParameter($parameter, $value);
            return;
        }

        $enseignement = $queryNameGenerator->generateJoinAlias('enseignement');
        $lien = $queryNameGenerator->generateJoinAlias('lien');
        $ue = $queryNameGenerator->generateJoinAlias('ue');
        $semestre = $queryNameGenerator->generateJoinAlias('semestre');
        $annee = $queryNameGenerator->generateJoinAlias('annee');
        $pn = $queryNameGenerator->generateJoinAlias('pn');
        $diplome = $queryNameGenerator->generateJoinAlias('diplome');

        $recherche = $queryBuilder->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(ScolEnseignement::class, $enseignement)
            ->join($enseignement.'.enseignementUes', $lien)
            ->join($lien.'.ue', $ue)
            ->join($ue.'.semestre', $semestre)
            ->join($semestre.'.annee', $annee)
            ->join($annee.'.pn', $pn)
            ->join($pn.'.diplome', $diplome)
            ->where(sprintf('%s.id = IDENTITY(%s.enseignement)', $enseignement, $alias));
        $champ = match ($property) {
            'semestre' => $semestre.'.id',
            'annee' => $annee.'.id',
            'diplome' => $diplome.'.id',
            'departement' => $diplome.'.departement',
        };
        $recherche->andWhere(sprintf('%s = :%s', $champ, $parameter));
        $queryBuilder->andWhere($queryBuilder->expr()->exists($recherche->getDQL()))
            ->setParameter($parameter, $value);
    }

    public function getDescription(string $resourceClass): array
    {

        return [
            'personnel' => [
                'property' => 'personnel',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by personnel',
                ],
            ],
            'anneeUniversitaire' => [
                'property' => 'anneeUniversitaire',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by anneeUniversitaire',
                ],
            ],
            'departement' => [
                'property' => 'departement',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by departement',
                ],
            ],
            'diplome' => [
                'property' => 'diplome',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by diplome',
                ],
            ],
            'semestre' => [
                'property' => 'semestre',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by semestre',
                ],
            ],
            'enseignement' => [
                'property' => 'enseignement',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by enseignement',
                ],
            ],
            'annee' => [
                'property' => 'annee',
                'type' => 'int',
                'required' => false,
                'openapi' => [
                    'description' => 'Filter by annee',
                ],
            ],
        ];
    }
}
