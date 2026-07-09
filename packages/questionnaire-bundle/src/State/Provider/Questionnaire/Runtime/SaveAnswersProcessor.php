<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireAnswer;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SaveAnswersInput;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SaveAnswersOutput;

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

        $questionIds = array_map(fn($a) => (int) ((array) $a)['questionId'], $data->answers);
        $questions = $this->em->getRepository(QuestionnaireQuestion::class)->findBy(['id' => $questionIds]);
        $qById = [];
        foreach ($questions as $q) { $qById[$q->getId()] = $q; }

        $existing = $this->em->getRepository(QuestionnaireAnswer::class)->findBy([
            'invitation' => $inv,
            'section' => $psi,
        ]);
        $aByQid = [];
        foreach ($existing as $a) { $aByQid[$a->getQuestion()->getId()] = $a; }

        foreach ($data->answers as $incoming) {
            $incomingArr = (array) $incoming;
            $qid = (int) ($incomingArr['questionId'] ?? 0);
            if (!isset($qById[$qid])) { continue; }

            $incomingVal = $incomingArr['value'] ?? null;
            if (isset($aByQid[$qid])) {
                $aByQid[$qid]->setValue($incomingVal);
            } else {
                $ans = new QuestionnaireAnswer($inv, $psi, $qById[$qid], $incomingVal);
                $this->em->persist($ans);
            }
        }

        // Mark startedAt on first answer save
        if ($inv->getStartedAt() === null) {
            $inv->setStartedAt(new \DateTimeImmutable());
        }

        $this->em->flush();

        return new SaveAnswersOutput(true, new \DateTimeImmutable());
    }
}
