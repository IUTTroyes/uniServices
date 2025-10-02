<?php

namespace App\Repository;

use App\Entity\ResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResetToken>
 *
 * @method ResetToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method ResetToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method ResetToken[]    findAll()
 * @method ResetToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetToken::class);
    }

    public function findOneByToken(string $token): ?ResetToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function removeExpiredTokens(): int
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.expiresAt < :now')
            ->setParameter('now', $now);

        return $qb->getQuery()->execute();
    }

    public function save(ResetToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ResetToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
