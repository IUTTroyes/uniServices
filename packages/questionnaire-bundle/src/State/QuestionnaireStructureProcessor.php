<?php

namespace QuestionnaireBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Écriture de la structure d'un questionnaire (sections et questions) :
 *  - le parent vient de l'URL, jamais du corps (on ne peut pas glisser une section dans le questionnaire d'un autre) ;
 *  - le droit de gérer le questionnaire parent est vérifié ici, une fois le parent connu ;
 *  - la structure est figée dès la publication : les réponses s'y rattachent (409) ;
 *  - les collections imbriquées et les champs techniques sont refusés (422).
 *
 * @implements ProcessorInterface<mixed, mixed>
 */
final class QuestionnaireStructureProcessor implements ProcessorInterface
{
    private const CHAMPS_SERVEUR_SECTION = ['questionnaire', 'questions', 'sectionInstances'];
    private const CHAMPS_SERVEUR_QUESTION = ['section', 'answers'];

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $remove,
        private readonly QuestionnaireResolver $questionnaires,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $request = $context['request'] ?? null;
        $creation = $operation instanceof Post;

        if ($data instanceof QuestionnaireSection) {
            if ($creation) {
                // L'ancien client envoie l'IRI du questionnaire : tolérée, mais c'est l'URL qui fait foi.
                ChampsEnvoyes::refuser($request, array_diff(self::CHAMPS_SERVEUR_SECTION, ['questionnaire']), $data);
                $data->setQuestionnaire($this->questionnaires->charger($uriVariables['questionnaireId'] ?? '', QuestionnaireVoter::CAN_EDIT_QUESTIONNAIRE));
            } else {
                ChampsEnvoyes::refuser($request, [...self::CHAMPS_SERVEUR_SECTION, 'uuid'], $data);
            }
            $questionnaire = $data->getQuestionnaire();
        } elseif ($data instanceof QuestionnaireQuestion) {
            if ($creation) {
                ChampsEnvoyes::refuser($request, array_diff(self::CHAMPS_SERVEUR_QUESTION, ['section']), $data);
                $data->setSection($this->section($uriVariables['questionnaireSectionId'] ?? ''));
            } else {
                ChampsEnvoyes::refuser($request, [...self::CHAMPS_SERVEUR_QUESTION, 'uuid'], $data);
            }
            $questionnaire = $data->getSection()?->getQuestionnaire();
        } else {
            return $this->persist->process($data, $operation, $uriVariables, $context);
        }

        $attribut = $data instanceof QuestionnaireSection
            ? ($operation instanceof DeleteOperationInterface ? QuestionnaireVoter::CAN_DELETE_QUESTION_SECTION : QuestionnaireVoter::CAN_EDIT_QUESTION_SECTION)
            : ($operation instanceof DeleteOperationInterface ? QuestionnaireVoter::CAN_DELETE_QUESTION : QuestionnaireVoter::CAN_EDIT_QUESTION);
        if ($questionnaire === null || !$this->security->isGranted($attribut, $data)) {
            throw new AccessDeniedHttpException('Accès refusé à ce questionnaire.');
        }
        if (!$questionnaire->estBrouillon()) {
            throw new ConflictHttpException('Le questionnaire est publié : sa structure ne peut plus être modifiée (dupliquez-le pour en faire une nouvelle version).');
        }

        if ($operation instanceof DeleteOperationInterface) {
            return $this->remove->process($data, $operation, $uriVariables, $context);
        }
        // L'éditeur choisit l'UUID des nouveaux éléments (les règles conditionnelles s'y réfèrent) : il doit être neuf.
        if ($creation && $this->em->getRepository($data::class)->count(['uuid' => $data->getUuid()]) > 0) {
            throw ChampsEnvoyes::violation($data, 'uuid', 'Cet identifiant est déjà utilisé.');
        }

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }

    private function section(mixed $uuid): QuestionnaireSection
    {
        $uuid = (string) $uuid;
        $section = Uuid::isValid($uuid) ? $this->em->getRepository(QuestionnaireSection::class)->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
        if (!$section instanceof QuestionnaireSection) {
            throw new NotFoundHttpException('Section introuvable.');
        }

        return $section;
    }
}
