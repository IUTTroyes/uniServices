<?php

namespace App\Domain\Questionnaire\Repeat;

use App\Entity\Questionnaires\Questionnaire;
use App\Entity\Questionnaires\QuestionnaireSection;

final readonly class RepeatItemsProviderRegistry
{
    /** @param iterable<RepeatItemsProviderInterface> $providers */
    public function __construct(private iterable $providers) {}

    /** @return list<RepeatItemView> */
    public function getItems(Questionnaire $q, QuestionnaireSection $section): array
    {
        $source = $section->getRepeatSource();
        if ($source === null) {
            return [];
        }

        foreach ($this->providers as $p) {
            if ($p->supports($source)) {
                return $p->getItems($q, $section);
            }
        }
        throw new \RuntimeException(sprintf('No provider for repeatSource "%s"', $source->getLibelle()));
    }
}
