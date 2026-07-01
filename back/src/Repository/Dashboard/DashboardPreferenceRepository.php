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
    public function findByPersonnel(Personnel $personnel, ?StructureDepartementPersonnel $structureDepartementPersonnel = null, string $dashboardCode = 'intranet'): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.personnel = :personnel')
            ->andWhere('p.structureDepartementPersonnel = :structureDepartementPersonnel')
            ->andWhere('p.dashboardCode = :dashboardCode')
            ->setParameter('personnel', $personnel)
            ->setParameter('structureDepartementPersonnel', $structureDepartementPersonnel)
            ->setParameter('dashboardCode', $dashboardCode)
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPersonnelAndWidgetKey(Personnel $personnel, string $widgetKey, ?StructureDepartementPersonnel $structureDepartementPersonnel = null, string $dashboardCode = 'intranet'): ?DashboardPreference
    {
        return $this->findOneBy([
            'personnel' => $personnel,
            'widgetKey' => $widgetKey,
            'structureDepartementPersonnel' => $structureDepartementPersonnel,
            'dashboardCode' => $dashboardCode,
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
