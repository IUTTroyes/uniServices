<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Analytics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Services\Analytics\QuestionnaireAnalyticsService;
use QuestionnaireBundle\ApiDto\Questionnaire\Analytics\QuestionnaireAnalyticsDto;

final class QuestionnaireAnalyticsProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireAnalyticsService $analyticsService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): QuestionnaireAnalyticsDto
    {
        $surveyId = (string) $uriVariables['surveyId'];

        $q = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => $surveyId]);
        if (!$q) {
            throw new \RuntimeException('Questionnaire not found');
        }

        return $this->analyticsService->getAnalytics($q);
    }
}
