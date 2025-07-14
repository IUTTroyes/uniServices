<?php

namespace App\Repository\Questionnaires;

use App\Entity\Questionnaires\QuestionnaireSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionnaireSection>
 */
class QuestionnaireSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionnaireSection::class);
    }
}
