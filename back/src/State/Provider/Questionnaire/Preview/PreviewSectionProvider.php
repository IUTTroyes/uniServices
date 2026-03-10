<?php

namespace App\State\Questionnaire\Preview;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Questionnaire\Preview\PreviewSectionDto;
use App\Domain\Questionnaire\Mapping\QuestionRuntimeMapper;
use App\Entity\Questionnaires\Questionnaire;
use App\Enum\QuestTypeQuestionEnum;
use App\Enum\QuestTypeRepeatEnum;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PreviewSectionProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private QuestionRuntimeMapper  $mapper
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PreviewSectionDto
    {
        $qid = (string) $uriVariables['questionnaireUuid'];
        $key = (string) $uriVariables['key'];

        $q = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => $qid]);
        if (!$q) {
            throw new \RuntimeException('Questionnaire not found');
        }

        // key format: tpl:12|matiere:45
        [$tplId, $repeatType, $repeatId] = $this->parseKey($key);

        $sectionTemplate = null;
        foreach ($q->getSections() as $st) {
            if ((int)$st->getId() === $tplId) { $sectionTemplate = $st; break; }
        }
        if (!$sectionTemplate) {
            throw new \RuntimeException('Section template not found');
        }

        $questions = [];
        foreach ($sectionTemplate->getQuestions() as $qt) {
            $questions[] = $this->mapper->map($qt, null); // pas de sauvegarde en preview
        }

        $title = $this->buildTitleSnapshot($sectionTemplate->getTitle(), $repeatType, $repeatId);

        return new PreviewSectionDto(
            questionnaireUuid: $q->getUuidString(),
            key: $key,
            title: $title,
            repeatItemType: $repeatType,
            repeatItemId: $repeatId === 'none' ? null : $repeatId,
            questions: $questions
        );
    }

    private function parseKey(string $key): array
    {
        // "tpl:12|matiere:45"
        $parts = explode('|', $key);
        if (count($parts) !== 2) { throw new \RuntimeException('Invalid key'); }

        $tpl = (int) str_replace('tpl:', '', $parts[0]);
        [$t, $id] = explode(':', $parts[1], 2);

        return [$tpl, QuestTypeRepeatEnum::tryFrom($t), $id];
    }

    private function buildTitleSnapshot(string $baseTitle, ?QuestTypeRepeatEnum $repeatType, string $repeatId): string
    {
        if ($repeatType === null) {
            return $baseTitle;
        }
        // En preview, si tu veux afficher un label humain, tu peux le calculer dans PreviewIndex
        return sprintf('%s – %s %s', $baseTitle, $repeatType->value, $repeatId);
    }
}
