<?php

namespace QuestionnaireBundle\Repository\Questionnaires;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use Doctrine\Persistence\ManagerRegistry;

class QuestionnaireQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionnaireQuestion::class);
    }
}
