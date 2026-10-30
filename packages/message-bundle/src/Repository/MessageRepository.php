<?php

namespace MessageBundle\Repository;

use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MessageBundle\Entity\Message;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[]
     */
    public function findSentByPersonnel(Personnel $personnel, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.destinataires', 'd')
            ->addSelect('d')
            ->where('m.expediteur = :expediteur')
            ->setParameter('expediteur', $personnel)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

