<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Analytics;

use Symfony\Component\Serializer\Attribute\Groups;

final class SectionInstanceAnalyticsDto
{
    /**
     * @param list<QuestionStatsDto> $questions
     */
    public function __construct(
        #[Groups(['questionnaire:read'])]
        public int $sectionInstanceId,
        #[Groups(['questionnaire:read'])]
        public string $sectionTitle,
        #[Groups(['questionnaire:read'])]
        public string $sectionType,
        #[Groups(['questionnaire:read'])]
        public ?string $repeatItemType,
        #[Groups(['questionnaire:read'])]
        public ?string $repeatItemId,
        #[Groups(['questionnaire:read'])]
        public array $questions
    ) {}
}
