<?php

namespace QuestionnaireBundle\Repository\Questionnaires;

use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionnaireSectionInstanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionnaireSectionInstance::class);
    }
}
