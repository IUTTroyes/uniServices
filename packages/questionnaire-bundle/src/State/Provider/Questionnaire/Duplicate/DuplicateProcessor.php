<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Duplicate;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use QuestionnaireBundle\ApiDto\Questionnaire\Duplicate\DuplicateInputDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Duplicate\DuplicateOutputDto;
use QuestionnaireBundle\Domain\Questionnaire\Duplicate\DuplicateQuestionnaireService;
use App\Entity\Users\Personnel;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;

final class DuplicateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly Security $security,
        private readonly DuplicateQuestionnaireService $service
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DuplicateOutputDto
    {
        /** @var DuplicateInputDto $data */
        $q = $this->questionnaires->charger($uriVariables['questionnaireUuid'] ?? '', QuestionnaireVoter::CAN_EDIT_QUESTIONNAIRE);
        // La copie reste dans le département de l'original : il faut pouvoir y créer un questionnaire.
        if (!$this->security->isGranted(QuestionnaireVoter::CREATE, $q)) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas créer de questionnaire dans ce département.');
        }

        $user = $this->security->getUser();
        $result = $this->service->duplicate($q, $data->newTitle ?? null, $user instanceof Personnel ? $user : null);

        /** @var Questionnaire $duplicate */
        $duplicate = $result['questionnaire'];

        return new DuplicateOutputDto(
            uuid: (string)$duplicate->getUuid(),
            title: $duplicate->getTitle(),
            status: $duplicate->getStatus()->value,
            sectionsCount: $result['sectionsCount'],
            questionsCount: $result['questionsCount']
        );
    }
}
