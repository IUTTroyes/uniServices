<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Analytics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\Analytics\QuestionnaireAnalyticsService;
use QuestionnaireBundle\ApiDto\Questionnaire\Analytics\QuestionnaireAnalyticsDto;

final class QuestionnaireAnalyticsProvider implements ProviderInterface
{
    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly Security $security,
        private readonly QuestionnaireAccess $access,
        private readonly QuestionnaireAnalyticsService $analyticsService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): QuestionnaireAnalyticsDto
    {
        $q = $this->questionnaires->charger($uriVariables['surveyId'] ?? '', QuestionnaireVoter::SYNTHESE);
        // Un destinataire des résultats ne voit que ses sections et ne lit pas le questionnaire lui-même.
        $analytics = $this->analyticsService->getAnalytics($q, $this->access->sectionsVisibles($this->security->getUser(), $q));
        $analytics->gestion = $this->security->isGranted(QuestionnaireVoter::VIEW, $q);

        return $analytics;
    }
}
