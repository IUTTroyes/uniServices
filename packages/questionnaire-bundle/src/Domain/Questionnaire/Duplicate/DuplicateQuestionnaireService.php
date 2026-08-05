<?php

namespace QuestionnaireBundle\Domain\Questionnaire\Duplicate;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use Symfony\Component\Uid\Uuid;

final class DuplicateQuestionnaireService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    public function duplicate(Questionnaire $source, ?string $newTitle = null): array
    {
        $this->em->beginTransaction();
        try {
            $duplicate = new Questionnaire();
            $duplicate->setUuid(Uuid::v4());
            $duplicate->setTitle($newTitle && trim($newTitle) !== '' ? trim($newTitle) : ($source->getTitle() . ' (Copie)'));
            $duplicate->setDescription($source->getDescription());
            $duplicate->setStatus(QuestStatutEnum::DRAFT);
            $duplicate->setOpeningDate(null);
            $duplicate->setClosingDate(null);
            $duplicate->setOpt($source->getOpt() ?? []);

            $this->em->persist($duplicate);

            // 1. Build UUID mapping dictionaries
            $sectionUuidMap = [];
            $questionUuidMap = [];

            $sectionsCount = 0;
            $questionsCount = 0;

            /** @var QuestionnaireQuestion[] $allClonedQuestions */
            $allClonedQuestions = [];

            foreach ($source->getSections() as $section) {
                $sectionsCount++;
                $newSection = new QuestionnaireSection();
                $newSection->setUuid(Uuid::v4());
                $newSection->setTitle($section->getTitle());
                $newSection->setDescription($section->getDescription());
                $newSection->setSortOrder($section->getSortOrder());
                $newSection->setTypeSection($section->getTypeSection());
                $newSection->setOpt($section->getOpt() ?? []);
                $newSection->setQuestionnaire($duplicate);

                $sectionUuidMap[(string)$section->getUuid()] = (string)$newSection->getUuid();
                if ($section->getId()) {
                    $sectionUuidMap[(string)$section->getId()] = (string)$newSection->getUuid();
                }

                $this->em->persist($newSection);

                foreach ($section->getQuestions() as $q) {
                    $questionsCount++;
                    $newQ = new QuestionnaireQuestion();
                    $newQ->setUuid(Uuid::v4());
                    $newQ->setLabel($q->getLabel());
                    $newQ->setTypeQuestion($q->getTypeQuestion());
                    $newQ->setRequired((bool)$q->isObligatoire());
                    $newQ->setHelp($q->getHelp());
                    $newQ->setChoices($q->getChoices());
                    $newQ->setConditionalRules($q->getConditionalRules());
                    $newQ->setSortOrder($q->getSortOrder());
                    $newQ->setOpt($q->getOpt() ?? []);
                    $newQ->setSection($newSection);

                    $questionUuidMap[(string)$q->getUuid()] = (string)$newQ->getUuid();
                    if ($q->getId()) {
                        $questionUuidMap[(string)$q->getId()] = (string)$newQ->getUuid();
                    }

                    $this->em->persist($newQ);
                    $allClonedQuestions[] = $newQ;
                }
            }

            // 2. Re-map conditional rules across all cloned questions
            foreach ($allClonedQuestions as $newQ) {
                $rules = $newQ->getConditionalRules();
                if (!empty($rules) && is_array($rules)) {
                    $remappedRules = [];
                    $ruleList = isset($rules[0]) && is_array($rules[0]) ? $rules : [$rules];

                    foreach ($ruleList as $r) {
                        if (!is_array($r)) continue;

                        $dependsOn = (string)($r['dependsOnQuestionId'] ?? $r['dependsOn'] ?? '');
                        if (isset($questionUuidMap[$dependsOn])) {
                            $r['dependsOn'] = $questionUuidMap[$dependsOn];
                            $r['dependsOnQuestionId'] = $questionUuidMap[$dependsOn];
                        }

                        if (isset($r['conditions']) && is_array($r['conditions'])) {
                            $newConditions = [];
                            foreach ($r['conditions'] as $cond) {
                                if (!is_array($cond)) continue;
                                $cDep = (string)($cond['dependsOnQuestionId'] ?? $cond['dependsOn'] ?? '');
                                if (isset($questionUuidMap[$cDep])) {
                                    if (isset($cond['dependsOn'])) {
                                        $cond['dependsOn'] = $questionUuidMap[$cDep];
                                    }
                                    if (isset($cond['dependsOnQuestionId'])) {
                                        $cond['dependsOnQuestionId'] = $questionUuidMap[$cDep];
                                    }
                                }
                                $newConditions[] = $cond;
                            }
                            $r['conditions'] = $newConditions;
                        }

                        if (isset($r['targetQuestionIds']) && is_array($r['targetQuestionIds'])) {
                            $newTargets = [];
                            foreach ($r['targetQuestionIds'] as $tid) {
                                $tidStr = (string)$tid;
                                $newTargets[] = $questionUuidMap[$tidStr] ?? $tid;
                            }
                            $r['targetQuestionIds'] = $newTargets;
                        }

                        if (isset($r['targetSectionId'])) {
                            $secIdStr = (string)$r['targetSectionId'];
                            if (isset($sectionUuidMap[$secIdStr])) {
                                $r['targetSectionId'] = $sectionUuidMap[$secIdStr];
                            }
                        }

                        $remappedRules[] = $r;
                    }
                    $newQ->setConditionalRules($remappedRules);
                }
            }

            $this->em->flush();
            $this->em->commit();

            return [
                'questionnaire' => $duplicate,
                'sectionsCount' => $sectionsCount,
                'questionsCount' => $questionsCount,
            ];
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
