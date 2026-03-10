<?php

namespace App\State\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Questionnaire\Runtime\SectionRuntimeDto;
use App\Domain\Questionnaire\Mapping\QuestionRuntimeMapper;
use App\Entity\Questionnaires\QuestionnaireInvitation;
use App\Entity\Questionnaires\QuestionnaireAnswer;
use App\Entity\Questionnaires\QuestionnaireSectionInstance;
use Doctrine\ORM\EntityManagerInterface;

final class InvitationSectionProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private QuestionRuntimeMapper $mapper
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SectionRuntimeDto
    {
        $token = (string) $uriVariables['token'];
        $id = (int) $uriVariables['id'];

        $inv = $this->em->getRepository(QuestionnaireInvitation::class)->findOneBy(['token' => $token]);
        if (!$inv) { throw new \RuntimeException('Invitation not found'); }

        $psi = $this->em->getRepository(QuestionnaireSectionInstance::class)->find($id);
        if (!$psi || $psi->getQuestionnaire()->getId() !== $inv->getQuestionnaire()->getId()) {
            throw new \RuntimeException('Section not found');
        }

        // Charger answers existantes pour cette section
        $answers = $this->em->getRepository(QuestionnaireAnswer::class)->findBy([
            'invitation' => $inv,
            'publishedSectionInstance' => $psi,
        ]);

        $answersByQid = [];
        foreach ($answers as $a) {
            $answersByQid[$a->getQuestionTemplate()->getId()] = $a->getValue();
        }

        $questions = [];
        foreach ($psi->getSectionTemplate()->getQuestions() as $qt) {
            $questions[] = $this->mapper->map($qt, $answersByQid[$qt->getId()] ?? null);
        }

        return new SectionRuntimeDto(
            questionnaireTitle: $inv->getQuestionnaire()->getTitle(),
            publishedSectionInstanceId: $psi->getId(),
            title: $psi->getTitleSnapshot(),
            repeatItemType: $psi->getRepeatItemType(),
            repeatItemId: $psi->getRepeatItemId(),
            questions: $questions
        );
    }
}
