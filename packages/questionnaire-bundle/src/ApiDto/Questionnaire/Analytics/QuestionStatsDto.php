<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Analytics;

use Symfony\Component\Serializer\Attribute\Groups;

final class QuestionStatsDto
{
    /**
     * @param array<string, mixed> $stats
     */
    public function __construct(
        #[Groups(['questionnaire:read'])]
        public string $questionId,
        #[Groups(['questionnaire:read'])]
        public string $questionLabel,
        #[Groups(['questionnaire:read'])]
        public string $questionType,
        #[Groups(['questionnaire:read'])]
        public int $totalResponses,
        #[Groups(['questionnaire:read'])]
        public array $stats
    ) {}
}
