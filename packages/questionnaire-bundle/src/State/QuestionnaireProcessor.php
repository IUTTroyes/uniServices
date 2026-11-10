<?php

namespace QuestionnaireBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Création / modification d'un questionnaire (le voter a déjà vérifié l'accès) :
 *  - les champs tenus par le serveur (statut, publication, auteur, bilan de clôture, collections) sont refusés (422) ;
 *  - à la création : brouillon, auteur = personne connectée ;
 *  - l'anonymat ne peut plus changer une fois le questionnaire publié (sinon on pourrait lever l'anonymat des réponses) ;
 *  - la date de clôture doit suivre la date d'ouverture.
 *
 * @implements ProcessorInterface<mixed, mixed>
 */
final class QuestionnaireProcessor implements ProcessorInterface
{
    public const CHAMPS_SERVEUR = [
        'uuid', 'status', 'publishedAt', 'author', 'completionNotified', 'completionNotifiedAt',
        'sections', 'sectionInstances', 'invitations', 'totalInvited', 'totalResponses', 'created', 'updated',
        'clotureLe', 'diffusionValideeLe', 'resultatsPubliesLe', 'etapeResultats', 'anneeUniversitaire',
        'modeleOrigine', 'modeleOrigineResume',
    ];

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        private readonly Security $security,
        private readonly QuestionnaireReglages $reglages,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Questionnaire) {
            return $this->persist->process($data, $operation, $uriVariables, $context);
        }
        $request = $context['request'] ?? null;
        ChampsEnvoyes::refuser($request, self::CHAMPS_SERVEUR, $data);

        $avant = ($context['previous_data'] ?? null) instanceof Questionnaire ? $context['previous_data'] : null;
        if ($avant !== null && !$operation instanceof Post) {
            // Un modèle reste un modèle (et inversement) : on en crée un nouveau par « Enregistrer comme modèle ».
            ChampsEnvoyes::refuser($request, ['estModele'], $data);
        }

        if ($operation instanceof Post || $avant === null) {
            $data->setStatus(QuestStatutEnum::DRAFT);
            $data->setPublishedAt(null);
            $user = $this->security->getUser();
            $data->setAuthor($user instanceof Personnel ? $user : null);
            // Politique du département (anonymat si l'éditeur ne l'a pas fixé, modification des réponses, seuil).
            $this->reglages->appliquerALaCreation($data, self::anonymatEnvoye($request));
            // Campagne de l'année universitaire en cours (comparaison d'une année sur l'autre).
            $data->setAnneeUniversitaire($this->em->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['actif' => true], ['id' => 'DESC']));
        } elseif (!$avant->estBrouillon()) {
            // Après publication, l'anonymat et son seuil sont figés (sinon on pourrait identifier les répondants).
            if ($avant->estAnonyme() !== $data->estAnonyme()) {
                throw new ConflictHttpException('L\'anonymat d\'un questionnaire publié ne peut plus être modifié.');
            }
            if ($avant->seuilResultats() !== $data->seuilResultats()) {
                throw new ConflictHttpException('Le seuil d\'anonymat d\'un questionnaire publié ne peut plus être modifié.');
            }
        }

        if ($data->getOpeningDate() !== null && $data->getClosingDate() !== null && $data->getClosingDate() <= $data->getOpeningDate()) {
            throw ChampsEnvoyes::violation($data, 'closingDate', 'La date de clôture doit être postérieure à la date d\'ouverture.');
        }

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }

    private static function anonymatEnvoye(?\Symfony\Component\HttpFoundation\Request $request): bool
    {
        try {
            $corps = $request?->toArray() ?? [];
        } catch (\Throwable) {
            return false;
        }

        return is_array($corps['opt'] ?? null) && array_key_exists('anonymous', $corps['opt']);
    }
}
