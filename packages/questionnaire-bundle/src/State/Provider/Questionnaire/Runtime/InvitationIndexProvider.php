<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\InvitationIndexDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SectionIndexDto;

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
            $qtCount = $s->getSection()->getQuestions()->count();
            $out[] = new SectionIndexDto(
                publishedSectionInstanceId: (int) $s->getId(),
                title: $s->getTitleSnapshot(),
                questionCount: $qtCount,
                order: $s->getSortOrder(),
                repeatItemType: $s->getRepeatSectionItemType(),
                repeatItemId: $s->getRepeatSectionItemId()
            );
        }

        return new InvitationIndexDto(
            questionnaireTitle: $q->getTitle(),
            invitationStatus: $inv->getStatus()->value,
            startedAt: $inv->getStartedAt(),
            submittedAt: $inv->getSubmittedAt(),
            sections: $out
        );
    }
}
