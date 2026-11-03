<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureDiplome;
use App\Filter\DiplomeFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureDiplome>
 */
class StructureDiplomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly DiplomeFilter $diplomeFilter)
    {
        parent::__construct($registry, StructureDiplome::class);
    }

    /** @return StructureDiplome[] */
    public function findByAnneeUniversitaire(int $anneeUniversitaire): array
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $this->diplomeFilter->apply($queryBuilder, new QueryNameGenerator(), StructureDiplome::class, context: [
            'filters' => ['anneeUniversitaire' => $anneeUniversitaire],
        ]);
        return $queryBuilder->getQuery()->getResult();
    }
}
