<?php

namespace App\Repository\Dashboard;

use App\Entity\Dashboard\DashboardPreference;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DashboardPreference>
 */
class DashboardPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DashboardPreference::class);
    }

    /**
     * @return DashboardPreference[]
     */
    public function findByPersonnel(Personnel $personnel, ?StructureDepartementPersonnel $structureDepartementPersonnel = null): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.personnel = :personnel')
            ->andWhere('p.structureDepartementPersonnel = :structureDepartementPersonnel')
            ->setParameter('personnel', $personnel)
            ->setParameter('structureDepartementPersonnel', $structureDepartementPersonnel)
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPersonnelAndWidgetKey(Personnel $personnel, string $widgetKey, ?StructureDepartementPersonnel $structureDepartementPersonnel = null): ?DashboardPreference
    {
        return $this->findOneBy([
            'personnel' => $personnel,
            'widgetKey' => $widgetKey,
            'structureDepartementPersonnel' => $structureDepartementPersonnel,
        ]);
    }

    public function save(DashboardPreference $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
