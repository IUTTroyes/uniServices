<?php

namespace DocumentBundle\Repository;

use DocumentBundle\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * @return Document[]
     */
    public function findFavorites(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isFavorite = true')
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Document[]
     */
    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.category = :catId')
            ->setParameter('catId', $categoryId)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
