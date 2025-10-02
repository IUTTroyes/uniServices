<?php

namespace App\Repository\Questionnaires;

use App\Entity\Questionnaires\QuestionnaireQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionnaireQuestion>
 */
class QuestionnaireQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionnaireQuestion::class);
    }
}
