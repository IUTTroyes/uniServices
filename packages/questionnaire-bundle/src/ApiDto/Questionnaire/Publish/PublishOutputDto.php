<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Publish;


use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use QuestionnaireBundle\State\Provider\Questionnaire\Publish\PublishProcessor;
use QuestionnaireBundle\Enum\QuestStatutEnum;

#[ApiResource(
    operations: [
        new Post(
            // Droit sur le questionnaire vérifié par le provider / processeur (QuestionnaireResolver).
            security: "is_granted('IS_PERSONNEL')",
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
        public int                $invitationsCount,
        /** Invitations dont l'e-mail est parti (les autres pourront être relancées). */
        public int                $emailsCount = 0,
    )
    {
    }
}
