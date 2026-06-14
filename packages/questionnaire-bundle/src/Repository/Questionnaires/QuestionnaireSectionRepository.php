<?php

namespace QuestionnaireBundle\Repository\Questionnaires;

use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionnaireSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionnaireSection::class);
    }
}
