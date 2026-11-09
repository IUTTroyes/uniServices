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
        public array $questions,
        /** Répondants (réponses envoyées) ayant répondu à cette section. */
        #[Groups(['questionnaire:read'])]
        public int $reponses = 0,
        /** Questionnaire anonyme : section sous le seuil de réponses, détail masqué. */
        #[Groups(['questionnaire:read'])]
        public bool $masquee = false,
        #[Groups(['questionnaire:read'])]
        public ?string $enseignement = null,
        #[Groups(['questionnaire:read'])]
        public ?string $intervenant = null,
    ) {}
}
