<?php

namespace App\State\Questionnaire\Publish;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiDto\Questionnaire\Publish\PublishInputDto;
use App\ApiDto\Questionnaire\Publish\PublishOutputDto;
use App\Domain\Questionnaire\Publish\PublishQuestionnaireService;
use App\Entity\Questionnaires\Questionnaire;
use Doctrine\ORM\EntityManagerInterface;

final class PublishProcessor implements ProcessorInterface
{
    public function __construct(
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
