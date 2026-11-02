<?php

namespace MessageBundle\Repository;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MessageBundle\Entity\Message;
use MessageBundle\Entity\MessageDestinataire;

/**
 * @extends ServiceEntityRepository<MessageDestinataire>
 */
class MessageDestinataireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageDestinataire::class);
    }

    /**
     * @param array{status?: string, search?: string} $filters
     * @return MessageDestinataire[]
     */
    public function findForUser(Personnel|Etudiant $user, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.message', 'm')
            ->leftJoin('m.expediteur', 'e')
            ->addSelect('m')
            ->addSelect('e');

        if ($user instanceof Etudiant) {
            $qb->where('d.etudiant = :user');
        } else {
            $qb->where('d.personnel = :user');
        }
        $qb->setParameter('user', $user);

        // Filter status: all, unread, archived, urgent
        $status = $filters['status'] ?? 'all';
        if ($status === 'unread') {
            $qb->andWhere('d.isLu = false')
               ->andWhere('d.isArchive = false');
        } elseif ($status === 'archived') {
            $qb->andWhere('d.isArchive = true');
        } elseif ($status === 'urgent' || $status === 'prioritaire') {
            $qb->andWhere('m.prioritaire = true')
               ->andWhere('d.isArchive = false');
        } else {
            // 'all' includes unarchived messages by default
            $qb->andWhere('d.isArchive = false');
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('LOWER(m.sujet) LIKE :search OR LOWER(m.contenu) LIKE :search OR LOWER(e.nom) LIKE :search OR LOWER(e.prenom) LIKE :search')
               ->setParameter('search', '%' . mb_strtolower((string) $filters['search']) . '%');
        }

        return $qb->orderBy('m.prioritaire', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadForUser(Personnel|Etudiant $user): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.isLu = false')
            ->andWhere('d.isArchive = false');

        if ($user instanceof Etudiant) {
            $qb->andWhere('d.etudiant = :user');
        } else {
            $qb->andWhere('d.personnel = :user');
        }

        return (int) $qb->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsReadForUser(Personnel|Etudiant $user): int
    {
        $qb = $this->createQueryBuilder('d')
            ->update()
            ->set('d.isLu', ':true')
            ->set('d.luAt', ':now')
            ->where('d.isLu = false')
            ->andWhere('d.isArchive = false')
            ->setParameter('true', true)
            ->setParameter('now', new \DateTimeImmutable());

        if ($user instanceof Etudiant) {
            $qb->andWhere('d.etudiant = :user');
        } else {
            $qb->andWhere('d.personnel = :user');
        }
        $qb->setParameter('user', $user);

        return (int) $qb->getQuery()->execute();
    }

    public function findOneByUser(int $messageId, Personnel|Etudiant $user): ?MessageDestinataire
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.message', 'm')
            ->addSelect('m')
            ->where('m.id = :messageId')
            ->setParameter('messageId', $messageId);

        if ($user instanceof Etudiant) {
            $qb->andWhere('d.etudiant = :user');
        } else {
            $qb->andWhere('d.personnel = :user');
        }

        return $qb->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array{total: int, readCount: int, percent: int, readers: array<int, array{id: int, nom: string, prenom: string, dateLecture: ?string, isLu: bool}>}
     */
    public function getReadStatisticsForMessage(Message $message): array
    {
        $destinataires = $this->createQueryBuilder('d')
            ->leftJoin('d.etudiant', 'et')
            ->leftJoin('d.personnel', 'p')
            ->addSelect('et')
            ->addSelect('p')
            ->where('d.message = :message')
            ->setParameter('message', $message)
            ->getQuery()
            ->getResult();

        $total = count($destinataires);
        $readCount = 0;
        $readers = [];

        /** @var MessageDestinataire $d */
        foreach ($destinataires as $d) {
            if ($d->isLu()) {
                $readCount++;
            }
            $targetUser = $d->getEtudiant() ?? $d->getPersonnel();
            if ($targetUser) {
                $readers[] = [
                    'id' => $targetUser->getId(),
                    'type' => $d->getEtudiant() !== null ? 'etudiant' : 'personnel',
                    'nom' => method_exists($targetUser, 'getNom') ? $targetUser->getNom() : '',
                    'prenom' => method_exists($targetUser, 'getPrenom') ? $targetUser->getPrenom() : '',
                    'email' => method_exists($targetUser, 'getEmail') ? $targetUser->getEmail() : '',
                    'isLu' => $d->isLu(),
                    'isCopie' => $d->isCopie(),
                    'dateLecture' => $d->getDateLecture()?->format('c'),
                ];
            }
        }

        return [
            'total' => $total,
            'readCount' => $readCount,
            'percent' => $total > 0 ? (int) round(($readCount / $total) * 100) : 0,
            'readers' => $readers,
        ];
    }
}

