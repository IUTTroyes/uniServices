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

    /**
     * @return array{total: int, submitted: int, pending: int, rate: float}
     */
    public function getParticipationStats(Questionnaire $questionnaire): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id) as total')
            ->addSelect('SUM(CASE WHEN i.status = :submitted THEN 1 ELSE 0 END) as submitted')
            ->where('i.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire)
            ->setParameter('submitted', QuestInvitationStatusEnum::SUBMITTED);

        $res = $qb->getQuery()->getSingleResult();
        $total = (int) ($res['total'] ?? 0);
        $submitted = (int) ($res['submitted'] ?? 0);
        $rate = $total > 0 ? round(($submitted / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'submitted' => $submitted,
            'pending' => max(0, $total - $submitted),
            'rate' => $rate,
        ];
    }
}
