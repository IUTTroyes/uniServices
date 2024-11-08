<?php
/*
 * Copyright (c) 2021. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/htdocs/intranetV3/src/Repository/BCCentreFinancierRepository.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 19/09/2021 16:18
 */

namespace App\Repository;

use App\Entity\BCCentreFinancier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BCCentreFinancier|null find($id, $lockMode = null, $lockVersion = null)
 * @method BCCentreFinancier|null findOneBy(array $criteria, array $orderBy = null)
 * @method BCCentreFinancier[]    findAll()
 * @method BCCentreFinancier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<BCCentreFinancier>
 */
class BCCentreFinancierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BCCentreFinancier::class);
    }
}
