<?php

namespace App\Repository\Dashboard;

use App\Entity\Dashboard\DashboardPreference;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Etudiant;
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
    public function findByUser(Personnel|Etudiant $user, ?StructureDepartementPersonnel $structureDepartementPersonnel = null, string $dashboardCode = 'intranet'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.dashboardCode = :dashboardCode')
            ->setParameter('dashboardCode', $dashboardCode)
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        if ($user instanceof Personnel) {
            $qb
                ->andWhere('p.personnel = :personnel')
                ->setParameter('personnel', $user);

            $qb
                ->andWhere('p.structureDepartementPersonnel = :structureDepartementPersonnel')
                ->setParameter('structureDepartementPersonnel', $structureDepartementPersonnel);
        } else {
            $qb
                ->andWhere('p.etudiant = :etudiant')
                ->setParameter('etudiant', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneByUserAndWidgetKey(Personnel|Etudiant $user, string $widgetKey, ?StructureDepartementPersonnel $structureDepartementPersonnel = null, string $dashboardCode = 'intranet'): ?DashboardPreference
    {
        $criteria = [
            'widgetKey' => $widgetKey,
            'dashboardCode' => $dashboardCode,
        ];

        if ($user instanceof Personnel) {
            $criteria['personnel'] = $user;
            $criteria['structureDepartementPersonnel'] = $structureDepartementPersonnel;
        } else {
            $criteria['etudiant'] = $user;
        }

        return $this->findOneBy($criteria);
    }

    public function save(DashboardPreference $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
