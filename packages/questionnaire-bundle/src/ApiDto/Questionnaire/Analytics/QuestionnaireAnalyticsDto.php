<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Analytics;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use QuestionnaireBundle\State\Provider\Questionnaire\Analytics\QuestionnaireAnalyticsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/questionnaires/{surveyId}/analytics',
            provider: QuestionnaireAnalyticsProvider::class,
        ),
    ]
)]
final class QuestionnaireAnalyticsDto
{
    /**
     * @param array<string, int> $responsesByDate
     * @param array<string, int> $statusCounts
     * @param list<SectionInstanceAnalyticsDto> $sections
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups(['questionnaire:read'])]
        public string $surveyId,
        #[Groups(['questionnaire:read'])]
        public int $totalInvited,
        #[Groups(['questionnaire:read'])]
        public int $totalResponses,
        #[Groups(['questionnaire:read'])]
        public float $completionRate,
        #[Groups(['questionnaire:read'])]
        public float $averageTimeSpent,
        #[Groups(['questionnaire:read'])]
        public array $responsesByDate,
        #[Groups(['questionnaire:read'])]
        public array $statusCounts,
        #[Groups(['questionnaire:read'])]
        public array $sections
    ) {}
}
