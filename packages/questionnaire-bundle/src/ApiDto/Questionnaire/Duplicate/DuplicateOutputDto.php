<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Duplicate;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use QuestionnaireBundle\State\Provider\Questionnaire\Duplicate\DuplicateProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/questionnaires/{questionnaireUuid}/duplicate',
            openapi: new Operation(
                tags: ['Questionnaire Management'],
                summary: 'Dupliquer intégralement un questionnaire et transposer ses règles',
                parameters: [
                    new Parameter(
                        name: 'questionnaireUuid',
                        in: 'path',
                        description: 'L\'identifiant du questionnaire à dupliquer',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                ],
            ),
            input: DuplicateInputDto::class,
            processor: DuplicateProcessor::class,
        ),
    ],
)]
final class DuplicateOutputDto
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $status,
        public int $sectionsCount,
        public int $questionsCount
    ) {}
}
