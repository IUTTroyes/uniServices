<?php

namespace App\State\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiDto\Questionnaire\Runtime\SaveAnswersInput;
use App\ApiDto\Questionnaire\Runtime\SaveAnswersOutput;
use App\Entity\Questionnaires\QuestionnaireInvitation;
use App\Entity\Questionnaires\QuestionnaireQuestion;
use App\Entity\Questionnaires\QuestionnaireAnswer;
use App\Entity\Questionnaires\QuestionnaireSectionInstance;
use Doctrine\ORM\EntityManagerInterface;

final class SaveAnswersProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SaveAnswersOutput
    {
        /** @var SaveAnswersInput $data */
        $token = (string) $uriVariables['token'];

        $inv = $this->em->getRepository(QuestionnaireInvitation::class)->findOneBy(['token' => $token]);
        if (!$inv) { throw new \RuntimeException('Invitation not found'); }
        if ($inv->isSubmitted()) { throw new \RuntimeException('Already submitted'); }

        $psi = $this->em->getRepository(QuestionnaireSectionInstance::class)->find($data->publishedSectionInstanceId);
        if (!$psi || $psi->getQuestionnaire()->getId() !== $inv->getQuestionnaire()->getId()) {
            throw new \RuntimeException('Invalid section');
        }

        $questionIds = array_map(fn($a) => (int) $a->questionId, $data->answers);
        $questions = $this->em->getRepository(QuestionnaireQuestion::class)->findBy(['id' => $questionIds]);
        $qById = [];
        foreach ($questions as $q) { $qById[$q->getId()] = $q; }

        $existing = $this->em->getRepository(QuestionnaireAnswer::class)->findBy([
            'invitation' => $inv,
            'publishedSectionInstance' => $psi,
        ]);
        $aByQid = [];
        foreach ($existing as $a) { $aByQid[$a->getQuestionTemplate()->getId()] = $a; }

        foreach ($data->answers as $incoming) {
            $qid = (int) $incoming->questionId;
            if (!isset($qById[$qid])) { continue; }

            if (isset($aByQid[$qid])) {
                $aByQid[$qid]->setValue($incoming->value);
            } else {
                $ans = new QuestionnaireAnswer($inv, $psi, $qById[$qid], $incoming->value);
                $this->em->persist($ans);
            }
        }

        $this->em->flush();

        return new SaveAnswersOutput(true, new \DateTimeImmutable());
    }
}
