<?php

namespace App\ApiDto\Questionnaire\Publish;


use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Enum\QuestStatutEnum;
use App\State\Questionnaire\Publish\PublishProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/questionnaires/{questionnaireUuid}/publish',
            openapi: new Operation(
                tags: ['Questionnaire Aperçu et Publication'],
                summary: 'Générer et publier un questionnaire',
                parameters: [
                    new Parameter(
                        name: 'questionnaireUuid',
                        in: 'path',
                        description: 'L\'identifiant du questionnaire',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                ],
            ),
            input: PublishInputDto::class,
            processor: PublishProcessor::class,
        ),
    ],
)]
final class PublishOutputDto
{
    public function __construct(
        public string             $questionnaireUuid,
        public QuestStatutEnum    $status,
        public \DateTimeImmutable $publishedAt,
        public int                $sectionsCount,
        public int                $invitationsCount
    )
    {
    }
}
