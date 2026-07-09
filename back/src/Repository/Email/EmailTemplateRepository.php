<?php

namespace App\Repository\Email;

use App\Entity\Email\EmailTemplate;
use App\Entity\Structure\StructureDepartement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    /**
     * Trouve la personnalisation la plus précise pour une clé d'email et un département.
     *
     * Priorité :
     *   1. Personnalisation spécifique au département
     *   (pas de fallback global ici, géré par EmailTemplateResolver)
     */
    public function findByKeyAndDepartement(string $key, StructureDepartement $departement, string $locale = 'fr'): ?EmailTemplate
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.emailKey = :key')
            ->andWhere('et.departement = :departement')
            ->andWhere('et.locale = :locale')
            ->setParameter('key', $key)
            ->setParameter('departement', $departement)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une personnalisation globale (sans département spécifique).
     */
    public function findGlobal(string $key, string $locale = 'fr'): ?EmailTemplate
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.emailKey = :key')
            ->andWhere('et.departement IS NULL')
            ->andWhere('et.locale = :locale')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les personnalisations pour une clé d'email donnée (tous départements).
     *
     * @return EmailTemplate[]
     */
    public function findAllByKey(string $key): array
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.emailKey = :key')
            ->setParameter('key', $key)
            ->orderBy('et.locale', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les clés d'email qui ont été personnalisées pour un département.
     *
     * @return string[]
     */
    public function findCustomizedKeysByDepartement(StructureDepartement $departement): array
    {
        $results = $this->createQueryBuilder('et')
            ->select('et.emailKey')
            ->andWhere('et.departement = :departement')
            ->setParameter('departement', $departement)
            ->getQuery()
            ->getScalarResult();

        return array_column($results, 'emailKey');
    }
}
