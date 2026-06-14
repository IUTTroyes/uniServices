<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Preview;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use QuestionnaireBundle\Domain\Questionnaire\Structure\QuestionnaireStructureService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Preview\PreviewIndexDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Preview\PreviewSectionIndexDto;

final readonly class PreviewIndexProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private readonly QuestionnaireStructureService $structureService,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PreviewIndexDto
    {
        $uuid = $uriVariables['questionnaireUuid'];
        $q = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => $uuid]);
        if (!$q) {
            throw new \RuntimeException('Questionnaire not found');
        }

        $plan = $this->structureService->buildPlan($q);

        $sections = [];
        foreach ($plan as $row) {
            $st = $row['sectionTemplate'];
            $key = $this->makeKey($st->getId(), $row['repeatItemType'], $row['repeatItemId']);

            $sections[] = new PreviewSectionIndexDto(
                key: $key,
                title: $row['title'],
                questionCount: $st->getQuestions()->count(),
                repeatItemType: $row['repeatItemType'],
                repeatItemId: $row['repeatItemId'],
                sortOrder: $row['sortOrder']
            );
        }

        return new PreviewIndexDto(
            $q->getUuidString(),
            $q->getTitle(),
            $q->getOpt(),
            $q->getStartText(),
            $q->getEndText(),
            $sections);
    }

    private function makeKey(?int $sectionTemplateId, ?string $type, ?string $id): string
    {
        return sprintf('tpl:%d|%s:%s', (int)$sectionTemplateId, $type ?? 'none', $id ?? 'none');
    }
}
