<?php

namespace QuestionnaireBundle\Repository\Questionnaires;

use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Enum\QuestInvitationStatusEnum;

class QuestionnaireInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionnaireInvitation::class);
    }

    /**
     * @return array<QuestionnaireInvitation>
     */
    public function findPendingInvitationsForQuestionnaire(Questionnaire $questionnaire, \DateTimeImmutable $olderThan): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire)
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('statuses', [QuestInvitationStatusEnum::PENDING, QuestInvitationStatusEnum::STARTED])
            ->andWhere('i.remindedAt IS NULL OR i.remindedAt <= :olderThan')
            ->andWhere('i.createdAt <= :olderThan')
            ->setParameter('olderThan', $olderThan)
            ->getQuery()
            ->getResult();
    }
}
