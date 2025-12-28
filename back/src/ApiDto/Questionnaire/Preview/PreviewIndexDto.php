<?php

namespace App\ApiDto\Questionnaire\Preview;


use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Entity\Questionnaires\Questionnaire;
use App\State\Questionnaire\Preview\PreviewIndexProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/questionnaires/{questionnaireUuid}/preview/index',
            uriVariables: [
                'questionnaireUuid' => new Link(
                    fromClass: Questionnaire::class,
                    identifiers: ['uuid']
                ),
            ],
            openapi: new Operation(
                tags: ['Questionnaire Aperçu et Publication'],
                summary: 'Récupère l\'aperçu d\'un questionnaire',
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
        ),
    ],
    provider: PreviewIndexProvider::class,
)]
final class PreviewIndexDto
{
    /** @param list<PreviewSectionIndexDto> $sections */
    public function __construct(
        public string $questionnaireUuid,
        public string $title,
        public array $opt,
        public ?string $startText = null,
        public ?string $endText = null,
        public array $sections = []
    ) {
    }
}
