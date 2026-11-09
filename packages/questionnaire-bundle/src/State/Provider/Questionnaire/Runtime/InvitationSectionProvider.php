<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SectionRuntimeDto;
use QuestionnaireBundle\Domain\Questionnaire\Mapping\QuestionRuntimeMapper;
use QuestionnaireBundle\Domain\Questionnaire\Runtime\InvitationRuntime;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireAnswer;

/**
 * Une section publiée avec les réponses déjà enregistrées, pour le répondant (par le jeton de l'invitation).
 *
 * @implements ProviderInterface<SectionRuntimeDto>
 */
final class InvitationSectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionRuntimeMapper $mapper,
        private readonly InvitationRuntime $runtime,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SectionRuntimeDto
    {
        $invitation = $this->runtime->invitation($uriVariables['token'] ?? '');
        $section = $this->runtime->section($invitation, $uriVariables['id'] ?? null);

        $reponses = [];
        foreach ($this->em->getRepository(QuestionnaireAnswer::class)->findBy(['invitation' => $invitation, 'section' => $section]) as $reponse) {
            $reponses[$reponse->getQuestion()?->getId()] = $reponse->getValue();
        }

        $toutes = $this->runtime->toutesLesQuestions($invitation);
        $questions = [];
        foreach ($section->getSection()?->getQuestions() ?? [] as $question) {
            $questions[] = $this->mapper->map($question, $reponses[$question->getId()] ?? null, $toutes);
        }

        return new SectionRuntimeDto(
            questionnaireTitle: (string) $invitation->getQuestionnaire()->getTitle(),
            publishedSectionInstanceId: (int) $section->getId(),
            title: (string) $section->getTitleSnapshot(),
            repeatItemType: $section->getRepeatSectionItemType(),
            repeatItemId: $section->getRepeatSectionItemId(),
            questions: $questions,
        );
    }
}
