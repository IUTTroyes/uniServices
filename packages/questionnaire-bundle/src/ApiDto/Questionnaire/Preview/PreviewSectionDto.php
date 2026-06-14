<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Preview;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\State\Questionnaire\Preview\PreviewSectionProvider;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\QuestionRuntimeDto;
use QuestionnaireBundle\Enum\QuestTypeRepeatEnum;


#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/questionnaires/{questionnaireUuid}/preview/sections/{key}',
            uriVariables: [
                'questionnaireUuid' => new Link(
                    fromClass: Questionnaire::class,
                    identifiers: ['uuid']
                ),
                'key' => new Link(
                    fromClass: QuestionnaireSection::class,
                    identifiers: ['key']
                ),
            ],
            openapi: new Operation(
                tags: ['Questionnaire Aperçu et Publication'],
                summary: 'Récupère l\'aperçu d\'une section d\'un questionnaire'
            ),
            provider: PreviewSectionProvider::class,
        ),
    ],
)]
final class PreviewSectionDto
{
    /** @param list<QuestionRuntimeDto> $questions */
    public function __construct(
        public string     $questionnaireUuid,
        public string  $key,
        public string  $title,
        public ?QuestTypeRepeatEnum $repeatItemType,
        public ?string $repeatItemId,
        public array   $questions
    )
    {
    }
}
