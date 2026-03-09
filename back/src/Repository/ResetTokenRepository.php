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

    /**
     * Recherche sécurisée d'un token avec protection contre les timing attacks
     */
    public function findOneByTokenSecure(string $token): ?ResetToken
    {
        // Récupérer tous les tokens non expirés (limité pour performance)
        $tokens = $this->createQueryBuilder('rt')
            ->where('rt.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();

        // Comparaison sécurisée avec hash_equals pour éviter les timing attacks
        foreach ($tokens as $resetToken) {
            if (hash_equals($resetToken->getToken(), $token)) {
                return $resetToken;
            }
        }

        return null;
    }

    /**
     * @deprecated Utiliser findOneByTokenSecure() à la place
     */
    public function findOneByToken(string $token): ?ResetToken
    {
        return $this->findOneByTokenSecure($token);
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

    /**
     * Supprime tous les tokens actifs pour un utilisateur donné
     * (un seul token de reset actif à la fois par sécurité)
     */
    public function removeTokensForUser(object $user): int
    {
        $qb = $this->createQueryBuilder('rt')
            ->delete();

        if (method_exists($user, 'getTypeUser') && $user->getTypeUser() === 'etudiant') {
            $qb->where('rt.etudiant = :user');
        } else {
            $qb->where('rt.personnel = :user');
        }

        $qb->setParameter('user', $user);

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
