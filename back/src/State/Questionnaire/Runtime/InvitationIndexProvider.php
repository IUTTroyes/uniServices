<?php

namespace App\State\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Questionnaire\Runtime\InvitationIndexDto;
use App\ApiDto\Questionnaire\Runtime\SectionIndexDto;
use App\Entity\Questionnaires\QuestionnaireInvitation;
use App\Entity\Questionnaires\QuestionnaireSectionInstance;
use Doctrine\ORM\EntityManagerInterface;

final class InvitationIndexProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): InvitationIndexDto
    {
        $token = (string) $uriVariables['token'];

        $inv = $this->em->getRepository(QuestionnaireInvitation::class)->findOneBy(['token' => $token]);
        if (!$inv) { throw new \RuntimeException('Invitation not found'); }

        $inv->markStarted();
        $this->em->flush();

        $q = $inv->getQuestionnaire();

        $sections = $this->em->getRepository(QuestionnaireSectionInstance::class)->findBy(
            ['questionnaire' => $q],
            ['sortOrder' => 'ASC']
        );

        $out = [];
        foreach ($sections as $s) {
            $qtCount = $s->getSectionTemplate()->getQuestions()->count();
            $out[] = new SectionIndexDto(
                publishedSectionInstanceId: (int) $s->getId(),
                title: $s->getTitleSnapshot(),
                questionCount: $qtCount,
                order: $s->getSortOrder(),
                repeatItemType: $s->getRepeatItemType(),
                repeatItemId: $s->getRepeatItemId()
            );
        }

        return new InvitationIndexDto(
            questionnaireTitle: $q->getTitle(),
            invitationStatus: $inv->getStatus(),
            startedAt: $inv->getStartedAt(),
            submittedAt: $inv->getSubmittedAt(),
            sections: $out
        );
    }
}
