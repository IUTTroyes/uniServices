<?php

namespace QuestionnaireBundle\Repository\Questionnaires;

use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use QuestionnaireBundle\Enum\QuestStatutEnum;

class QuestionnaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Questionnaire::class);
    }

    /**
     * @return array<Questionnaire>
     */
    public function findActivePublished(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('q')
            ->where('q.status = :status')
            ->setParameter('status', QuestStatutEnum::PUBLISHED)
            ->andWhere('q.openingDate IS NULL OR q.openingDate <= :now')
            ->andWhere('q.closingDate IS NULL OR q.closingDate >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
