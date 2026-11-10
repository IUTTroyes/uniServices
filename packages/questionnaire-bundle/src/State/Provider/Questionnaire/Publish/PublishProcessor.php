<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Publish;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use QuestionnaireBundle\Domain\Questionnaire\Publish\PublishQuestionnaireService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Publish\PublishInputDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Publish\PublishOutputDto;

final class PublishProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface      $em,
        private readonly PublishQuestionnaireService $service
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PublishOutputDto
    {
        /** @var PublishInputDto $data */
        $id = $uriVariables['questionnaireUuid'];

        $q = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => $id]);
        if (!$q) {
            throw new \RuntimeException('Questionnaire not found');
        }
        if (!$this->security->isGranted('CAN_PUBLISH_QUESTIONNAIRE', $q)) {
            throw new AccessDeniedException();
        }


        $result = $this->service->publish($q, $data->recipients);

        return new PublishOutputDto(
            questionnaireUuid: $q->getUuid(),
            status: $q->getStatus(),
            publishedAt: $result['publishedAt'],
            sectionsCount: $result['sectionsCount'],
            invitationsCount: $result['invitationsCount']
        );
    }
}
