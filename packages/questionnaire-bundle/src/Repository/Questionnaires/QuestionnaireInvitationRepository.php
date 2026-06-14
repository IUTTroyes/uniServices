<?php

namespace QuestionnaireBundle\Repository\Questionnaires;

use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionnaireInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionnaireInvitation::class);
    }
}
