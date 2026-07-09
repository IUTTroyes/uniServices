<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\StudentInvitationDto;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Users\Etudiant;

final class StudentInvitationsProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof Etudiant) {
            return [];
        }

        $emails = array_filter([
            $user->getMailUniv() ? trim(mb_strtolower($user->getMailUniv())) : null,
            $user->getMailPerso() ? trim(mb_strtolower($user->getMailPerso())) : null
        ]);

        if (empty($emails)) {
            return [];
        }

        $invitations = $this->em->getRepository(QuestionnaireInvitation::class)->createQueryBuilder('i')
            ->select('i', 'q')
            ->join('i.questionnaire', 'q')
            ->where('i.email IN (:emails)')
            ->setParameter('emails', $emails)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($invitations as $inv) {
            $q = $inv->getQuestionnaire();
            $out[] = new StudentInvitationDto(
                uuid: (string)$q->getUuid(),
                title: $q->getTitle(),
                description: $q->getDescription(),
                estimatedTime: $q->getEstimatedTime(),
                deadline: $q->getClosingDate()?->format('d/m/Y'),
                token: $inv->getToken(),
                status: $inv->getStatus()?->value ?? 'pending',
                anonymous: $q->getOpt()['anonymous'] ?? true
            );
        }

        return $out;
    }
}
