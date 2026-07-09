<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use QuestionnaireBundle\Domain\Questionnaire\Mapping\QuestionRuntimeMapper;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireAnswer;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SectionRuntimeDto;

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
            'section' => $psi,
        ]);

        $answersByQid = [];
        foreach ($answers as $a) {
            $answersByQid[$a->getQuestion()->getId()] = $a->getValue();
        }

        $questions = [];
        foreach ($psi->getSection()->getQuestions() as $qt) {
            $questions[] = $this->mapper->map($qt, $answersByQid[$qt->getId()] ?? null);
        }

        return new SectionRuntimeDto(
            questionnaireTitle: $inv->getQuestionnaire()->getTitle(),
            publishedSectionInstanceId: $psi->getId(),
            title: $psi->getTitleSnapshot(),
            repeatItemType: $psi->getRepeatSectionItemType(),
            repeatItemId: $psi->getRepeatSectionItemId(),
            questions: $questions
        );
    }
}
