<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Duplicate;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Duplicate\DuplicateInputDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Duplicate\DuplicateOutputDto;
use QuestionnaireBundle\Domain\Questionnaire\Duplicate\DuplicateQuestionnaireService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;

final class DuplicateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
        private readonly DuplicateQuestionnaireService $service
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DuplicateOutputDto
    {
        /** @var DuplicateInputDto $data */
        $uuidStr = $uriVariables['questionnaireUuid'];

        $q = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => $uuidStr]);
        if (!$q) {
            throw new \RuntimeException('Questionnaire not found');
        }
        if (!$this->security->isGranted('CAN_EDIT_QUESTIONNAIRE', $q)) {
            throw new AccessDeniedException();
        }


        $result = $this->service->duplicate($q, $data->newTitle ?? null);

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
