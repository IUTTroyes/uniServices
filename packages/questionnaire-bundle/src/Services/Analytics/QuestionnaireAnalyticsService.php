<?php

namespace QuestionnaireBundle\Services\Analytics;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireAnswer;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use QuestionnaireBundle\Enum\QuestInvitationStatusEnum;
use QuestionnaireBundle\Enum\QuestTypeQuestionEnum;
use QuestionnaireBundle\Enum\QuestTypeSectionEnum;
use QuestionnaireBundle\ApiDto\Questionnaire\Analytics\QuestionnaireAnalyticsDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Analytics\SectionInstanceAnalyticsDto;
use QuestionnaireBundle\ApiDto\Questionnaire\Analytics\QuestionStatsDto;

class QuestionnaireAnalyticsService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function getAnalytics(Questionnaire $q): QuestionnaireAnalyticsDto
    {
        $invitations = $q->getInvitations();
        $totalInvited = count($invitations);
        $totalResponses = 0;
        $statusCounts = [
            'submitted' => 0,
            'started' => 0,
            'pending' => 0,
        ];
        $totalDuration = 0;
        $durationCount = 0;
        $responsesByDate = [];

        foreach ($invitations as $inv) {
            $status = $inv->getStatus() ? $inv->getStatus()->value : 'pending';
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            if ($status === 'submitted') {
                $totalResponses++;

                $startedAt = $inv->getStartedAt();
                $submittedAt = $inv->getSubmittedAt();
                if ($startedAt && $submittedAt) {
                    $diff = $submittedAt->getTimestamp() - $startedAt->getTimestamp();
                    if ($diff > 0) {
                        $totalDuration += $diff;
                        $durationCount++;
                    }
                }

                if ($submittedAt) {
                    $dateStr = $submittedAt->format('Y-m-d');
                    $responsesByDate[$dateStr] = ($responsesByDate[$dateStr] ?? 0) + 1;
                }
            }
        }

        $completionRate = $totalInvited > 0 ? ($totalResponses / $totalInvited) * 100 : 0.0;
        $averageTimeSpent = $durationCount > 0 ? $totalDuration / $durationCount : 0.0;

        // Fetch all answers for the questionnaire in a single query
        $answers = $this->em->getRepository(QuestionnaireAnswer::class)->createQueryBuilder('a')
            ->join('a.section', 's')
            ->where('s.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $q)
            ->getQuery()
            ->getResult();

        // Group answers by Section Instance and Question
        $answersMap = []; // [sectionInstanceId][questionId][] = value
        foreach ($answers as $ans) {
            if ($ans->getSection() === null || $ans->getQuestion() === null) {
                continue;
            }
            $secId = $ans->getSection()->getId();
            $qId = $ans->getQuestion()->getId();
            $val = $ans->getValue();

            if ($val !== null && $val !== '') {
                $answersMap[$secId][$qId][] = $val;
            }
        }

        // Fetch all section instances for the questionnaire
        $sectionInstances = $this->em->getRepository(QuestionnaireSectionInstance::class)->findBy(
            ['questionnaire' => $q],
            ['sortOrder' => 'ASC']
        );

        $sectionsAnalytics = [];

        foreach ($sectionInstances as $instance) {
            $sectionTemplate = $instance->getSection();
            if ($sectionTemplate === null) {
                continue;
            }

            $questionsStats = [];
            foreach ($sectionTemplate->getQuestions() as $question) {
                $qId = $question->getId();
                $qAnswers = $answersMap[$instance->getId()][$qId] ?? [];
                $qTotalResponses = count($qAnswers);
                $type = $question->getTypeQuestion();
                $typeStr = $type ? $type->value : 'text_short';

                $stats = [];

                if ($type === QuestTypeQuestionEnum::SingleChoice || $type === QuestTypeQuestionEnum::MultipleChoice) {
                    $choices = $question->getChoices() ?? [];
                    $optionTexts = [];
                    foreach ($choices as $choice) {
                        if (is_array($choice)) {
                            $optionTexts[] = $choice['text'] ?? $choice['label'] ?? '';
                        } elseif (is_string($choice)) {
                            $optionTexts[] = $choice;
                        }
                    }

                    $counts = array_fill_keys($optionTexts, 0);
                    foreach ($qAnswers as $ansVal) {
                        if (is_array($ansVal)) {
                            foreach ($ansVal as $item) {
                                if (is_string($item) && isset($counts[$item])) {
                                    $counts[$item]++;
                                }
                            }
                        } elseif (is_string($ansVal)) {
                            if (isset($counts[$ansVal])) {
                                $counts[$ansVal]++;
                            }
                        }
                    }

                    $statsList = [];
                    foreach ($optionTexts as $text) {
                        $cnt = $counts[$text];
                        $pct = $qTotalResponses > 0 ? ($cnt / $qTotalResponses) * 100 : 0.0;
                        $statsList[] = [
                            'text' => $text,
                            'count' => $cnt,
                            'percentage' => round($pct, 1),
                        ];
                    }
                    $stats = ['choices' => $statsList];
                } elseif ($type === QuestTypeQuestionEnum::Scale) {
                    $opts = $question->getOpt();
                    $min = $opts['min'] ?? 1;
                    $max = $opts['max'] ?? 10;

                    $sum = 0;
                    $numCount = 0;
                    $distribution = array_fill($min, $max - $min + 1, 0);

                    foreach ($qAnswers as $ansVal) {
                        $val = (int)$ansVal;
                        if ($val >= $min && $val <= $max) {
                            $sum += $val;
                            $numCount++;
                            $distribution[$val]++;
                        }
                    }

                    $average = $numCount > 0 ? $sum / $numCount : 0.0;

                    // Convert distribution to string keys for JSON serialization stability
                    $distMap = [];
                    foreach ($distribution as $k => $v) {
                        $distMap[(string)$k] = $v;
                    }

                    $stats = [
                        'min' => $min,
                        'max' => $max,
                        'average' => round($average, 1),
                        'distribution' => $distMap,
                    ];
                } elseif ($type === QuestTypeQuestionEnum::Ranking) {
                    $choices = $question->getChoices() ?? [];
                    $optionTexts = [];
                    foreach ($choices as $choice) {
                        if (is_array($choice)) {
                            $optionTexts[] = $choice['text'] ?? $choice['label'] ?? '';
                        } elseif (is_string($choice)) {
                            $optionTexts[] = $choice;
                        }
                    }

                    $rankSums = array_fill_keys($optionTexts, 0);
                    $rankCounts = array_fill_keys($optionTexts, 0);

                    foreach ($qAnswers as $ansVal) {
                        if (is_array($ansVal)) {
                            foreach ($ansVal as $pos => $text) {
                                if (is_string($text) && isset($rankSums[$text])) {
                                    $rankSums[$text] += ($pos + 1);
                                    $rankCounts[$text]++;
                                }
                            }
                        }
                    }

                    $statsList = [];
                    foreach ($optionTexts as $text) {
                        $cnt = $rankCounts[$text];
                        $avgRank = $cnt > 0 ? $rankSums[$text] / $cnt : 0.0;
                        $statsList[] = [
                            'text' => $text,
                            'averageRank' => round($avgRank, 1),
                        ];
                    }

                    $stats = ['ranking' => $statsList];
                } elseif ($type === QuestTypeQuestionEnum::TextShort || $type === QuestTypeQuestionEnum::TextLong) {
                    $totalLength = 0;
                    $textCount = 0;
                    $samples = [];

                    foreach ($qAnswers as $ansVal) {
                        if (is_string($ansVal) && trim($ansVal) !== '') {
                            $totalLength += mb_strlen($ansVal);
                            $textCount++;
                            $samples[] = $ansVal;
                        }
                    }

                    $avgLength = $textCount > 0 ? $totalLength / $textCount : 0.0;

                    $stats = [
                        'averageLength' => round($avgLength),
                        'samples' => $samples,
                    ];
                } elseif ($type === QuestTypeQuestionEnum::Matrix) {
                    $rows = [];
                    $columns = [];
                    $cellCounts = [];

                    foreach ($qAnswers as $ansVal) {
                        if (is_array($ansVal)) {
                            foreach ($ansVal as $row => $col) {
                                if (is_string($row) && is_string($col)) {
                                    if (!in_array($row, $rows, true)) {
                                        $rows[] = $row;
                                    }
                                    if (!in_array($col, $columns, true)) {
                                        $columns[] = $col;
                                    }
                                    $cellCounts[$row][$col] = ($cellCounts[$row][$col] ?? 0) + 1;
                                }
                            }
                        }
                    }

                    $defaultColumns = ['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément'];
                    usort($columns, function($a, $b) use ($defaultColumns) {
                        $posA = array_search($a, $defaultColumns, true);
                        $posB = array_search($b, $defaultColumns, true);
                        if ($posA === false && $posB === false) return strcmp($a, $b);
                        if ($posA === false) return 1;
                        if ($posB === false) return -1;
                        return $posA <=> $posB;
                    });

                    // Construct full grid counts
                    $grid = [];
                    foreach ($rows as $row) {
                        $grid[$row] = [];
                        foreach ($columns as $col) {
                            $grid[$row][$col] = $cellCounts[$row][$col] ?? 0;
                        }
                    }

                    $stats = [
                        'rows' => $rows,
                        'columns' => $columns,
                        'grid' => $grid,
                    ];
                }

                $questionsStats[] = new QuestionStatsDto(
                    questionId: (string)$qId,
                    questionLabel: $question->getLabel() ?? '',
                    questionType: $typeStr,
                    totalResponses: $qTotalResponses,
                    stats: $stats
                );
            }

            $sectionsAnalytics[] = new SectionInstanceAnalyticsDto(
                sectionInstanceId: (int)$instance->getId(),
                sectionTitle: $instance->getTitleSnapshot() ?? '',
                sectionType: $sectionTemplate->getTypeSection() ? $sectionTemplate->getTypeSection()->value : 'normal',
                repeatItemType: $instance->getRepeatSectionItemType(),
                repeatItemId: $instance->getRepeatSectionItemId() ? (string)$instance->getRepeatSectionItemId() : null,
                questions: $questionsStats
            );
        }

        return new QuestionnaireAnalyticsDto(
            surveyId: $q->getUuidString(),
            totalInvited: $totalInvited,
            totalResponses: $totalResponses,
            completionRate: round($completionRate, 1),
            averageTimeSpent: round($averageTimeSpent),
            responsesByDate: $responsesByDate,
            statusCounts: $statusCounts,
            sections: $sectionsAnalytics
        );
    }
}
