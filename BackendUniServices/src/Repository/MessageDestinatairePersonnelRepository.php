<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Repository/MessageDestinatairePersonnelRepository.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 18/04/2024 17:54
 */

namespace App\Repository;

use App\Entity\Constantes;
use App\Entity\Message;
use App\Entity\MessageDestinatairePersonnel;
use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MessageDestinatairePersonnel|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageDestinatairePersonnel|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageDestinatairePersonnel[]    findAll()
 * @method MessageDestinatairePersonnel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<MessageDestinatairePersonnel>
 */
class MessageDestinatairePersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageDestinatairePersonnel::class);
    }

    public function findLast(Personnel $user, int $nbMessage = 0, string $filtre = '', int $page = 0): array
    {
        $query = $this->createQueryBuilder('m')
            ->innerJoin(Message::class, 'mes', 'WITH', 'mes.id = m.message')
            ->innerJoin(Personnel::class, 'p', 'WITH', 'mes.expediteur = p.id')
            ->where('m.personnel = :personnel')
            ->setParameter('personnel', $user->getId())
            ->orderBy('m.created', Order::Descending->value);

        switch ($filtre) {
            case 'all':
                $query->andWhere('m.etat = :read or m.etat = :unread')
                    ->setParameter('read', 'R')
                    ->setParameter('unread', 'U');
                break;
            case 'trash':
                $query->andWhere('m.etat = :delete')
                    ->setParameter('delete', 'D');
                break;
            case 'starred':
                $query->andWhere('m.starred = 1');
                break;
        }

        if ($page > 0) {
            $query->setFirstResult($page * Constantes::NB_MESSAGE_PAR_PAGE);
        }
        if ($nbMessage > 0) {
            $query->setMaxResults($nbMessage);
        }

        return $query->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findDest(Personnel $user, Message $message): ?MessageDestinatairePersonnel
    {
        return $this->createQueryBuilder('m')
            ->where('m.personnel = :personnel')
            ->innerJoin(Message::class, 'mes', 'WITH', 'mes.id = m.message')
            ->innerJoin(Personnel::class, 'p', 'WITH', 'mes.expediteur = p.id')
            ->andWhere('m.message = :message')
            ->setParameter('personnel', $user->getId())
            ->setParameter('message', $message->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getNbUnread(Personnel $user): ?int
    {
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.personnel = :personnel')
            ->setParameter('personnel', $user->getId())
            ->andWhere('m.etat = :unread')
            ->setParameter('unread', 'U')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
