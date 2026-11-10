<?php

namespace QuestionnaireBundle\Domain\Questionnaire\Runtime;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Domain\Questionnaire\Mapping\QuestionRuntimeMapper;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireAnswer;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Règles communes aux écrans de réponse (accès par le jeton de l'invitation, sans compte requis) :
 * le jeton est la clé d'accès du répondant ; on ne répond qu'à un questionnaire publié, dans sa période d'ouverture,
 * et, une fois les réponses envoyées, seulement si le questionnaire autorise leur modification (réglage du département
 * recopié sur le questionnaire).
 */
final class InvitationRuntime
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionRuntimeMapper $mapper,
    ) {
    }

    public function invitation(mixed $token): QuestionnaireInvitation
    {
        $token = (string) $token;
        $invitation = $token !== '' && \strlen($token) <= 64
            ? $this->em->getRepository(QuestionnaireInvitation::class)->findOneBy(['token' => $token])
            : null;
        if (!$invitation instanceof QuestionnaireInvitation || $invitation->getQuestionnaire() === null) {
            throw new NotFoundHttpException('Invitation introuvable.');
        }

        return $invitation;
    }

    /** Section publiée appartenant au questionnaire de l'invitation (404 sinon). */
    public function section(QuestionnaireInvitation $invitation, mixed $id): QuestionnaireSectionInstance
    {
        $section = is_numeric($id) ? $this->em->getRepository(QuestionnaireSectionInstance::class)->find((int) $id) : null;
        if (!$section instanceof QuestionnaireSectionInstance || $section->getQuestionnaire()?->getId() !== $invitation->getQuestionnaire()->getId()) {
            throw new NotFoundHttpException('Section introuvable.');
        }

        return $section;
    }

    /** Le répondant peut-il (encore) enregistrer des réponses ? */
    public function estModifiable(QuestionnaireInvitation $invitation): bool
    {
        $questionnaire = $invitation->getQuestionnaire();

        return $questionnaire->estOuvert() && (!$invitation->isSubmitted() || $questionnaire->autoriseModificationReponses());
    }

    /** Peut-on encore enregistrer des réponses ? (409 sinon) */
    public function verifierModifiable(QuestionnaireInvitation $invitation): void
    {
        if (!$invitation->getQuestionnaire()->estOuvert()) {
            throw new ConflictHttpException('Ce questionnaire n\'est pas ouvert aux réponses.');
        }
        if ($invitation->isSubmitted() && !$invitation->getQuestionnaire()->autoriseModificationReponses()) {
            throw new ConflictHttpException('Vos réponses ont déjà été envoyées.');
        }
    }

    /** @return list<QuestionnaireQuestion> toutes les questions du questionnaire (règles conditionnelles inter-sections) */
    public function toutesLesQuestions(QuestionnaireInvitation $invitation): array
    {
        $questions = [];
        foreach ($invitation->getQuestionnaire()->getSections() as $section) {
            foreach ($section->getQuestions() as $question) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    /**
     * Questions obligatoires restées sans réponse. Seules les questions toujours affichées (sans règle d'affichage
     * conditionnel) sont contrôlées : une question conditionnelle peut légitimement rester masquée.
     *
     * @return list<string> libellés (section — question)
     */
    public function obligatoiresManquantes(QuestionnaireInvitation $invitation): array
    {
        $reponses = [];
        foreach ($this->em->getRepository(QuestionnaireAnswer::class)->findBy(['invitation' => $invitation]) as $reponse) {
            $reponses[$reponse->getSection()?->getId()][$reponse->getQuestion()?->getId()] = $reponse->getValue();
        }
        $toutes = $this->toutesLesQuestions($invitation);

        $manquantes = [];
        $sections = $this->em->getRepository(QuestionnaireSectionInstance::class)->findBy(['questionnaire' => $invitation->getQuestionnaire()], ['sortOrder' => 'ASC']);
        foreach ($sections as $section) {
            foreach ($section->getSection()?->getQuestions() ?? [] as $question) {
                if (!$question->isObligatoire() || $this->mapper->map($question, null, $toutes)->visibility !== null) {
                    continue;
                }
                if (self::estVide($reponses[$section->getId()][$question->getId()] ?? null)) {
                    $manquantes[] = sprintf('%s — %s', $section->getTitleSnapshot(), $question->getLabel());
                }
            }
        }

        return $manquantes;
    }

    public static function estVide(mixed $valeur): bool
    {
        return $valeur === null || $valeur === '' || $valeur === [] || (\is_string($valeur) && trim($valeur) === '');
    }
}
