<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SaveAnswersInput;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SaveAnswersOutput;
use QuestionnaireBundle\Domain\Questionnaire\Runtime\InvitationRuntime;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireAnswer;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Enregistrement (brouillon) des réponses d'une section publiée, par le jeton de l'invitation.
 * Seules les questions de CETTE section sont acceptées ; questionnaire ouvert et invitation non soumise.
 *
 * @implements ProcessorInterface<SaveAnswersInput, SaveAnswersOutput>
 */
final class SaveAnswersProcessor implements ProcessorInterface
{
    /** Taille maximale d'une réponse sérialisée (texte libre compris). */
    private const TAILLE_MAX = 20000;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvitationRuntime $runtime,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SaveAnswersOutput
    {
        /** @var SaveAnswersInput $data */
        $invitation = $this->runtime->invitation($uriVariables['token'] ?? '');
        $this->runtime->verifierModifiable($invitation);
        $section = $this->runtime->section($invitation, $data->publishedSectionInstanceId);

        $questions = [];
        foreach ($section->getSection()?->getQuestions() ?? [] as $question) {
            $questions[$question->getId()] = $question;
        }

        $existantes = [];
        foreach ($this->em->getRepository(QuestionnaireAnswer::class)->findBy(['invitation' => $invitation, 'section' => $section]) as $reponse) {
            $existantes[$reponse->getQuestion()?->getId()] = $reponse;
        }

        foreach ($data->answers as $entree) {
            $id = (int) ($entree['questionId'] ?? 0);
            if (!isset($questions[$id])) {
                throw new UnprocessableEntityHttpException(sprintf('La question %d n\'appartient pas à cette section.', $id));
            }
            $valeur = $entree['value'] ?? null;
            if (\strlen((string) json_encode($valeur)) > self::TAILLE_MAX) {
                throw new UnprocessableEntityHttpException('Réponse trop longue.');
            }
            if (isset($existantes[$id])) {
                $existantes[$id]->setValue($valeur);
            } else {
                $existantes[$id] = new QuestionnaireAnswer($invitation, $section, $questions[$id], $valeur);
                $this->em->persist($existantes[$id]);
            }
        }

        $invitation->markStarted();
        $this->em->flush();

        return new SaveAnswersOutput(true, new \DateTimeImmutable());
    }
}
