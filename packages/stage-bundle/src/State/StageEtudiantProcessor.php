<?php

namespace StageBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Security\StageAccess;
use StageBundle\Service\Notification\StageNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Création / modification d'un dossier de stage :
 *  1. contrôle des champs selon le profil (le voter a déjà vérifié l'accès au dossier) ;
 *  2. enregistrement ;
 *  3. notifications (changement de statut, dépôt, tuteur affecté).
 *
 * @implements ProcessorInterface<mixed, mixed>
 */
final class StageEtudiantProcessor implements ProcessorInterface
{
    /** Champs réservés au service des stages : un étudiant ne peut pas les écrire. */
    private const INTERDITS_ETUDIANT = [
        'tuteurUniversitaire', 'evaluationNote', 'evaluationCommentaire', 'suiviRencontres',
        'dateValidation', 'dateConventionEnvoyee', 'dateConventionRecu', 'dateImprime', 'dateAutorise',
    ];
    /** Seuls champs qu'un tuteur universitaire (sans autre responsabilité) peut écrire. */
    private const AUTORISES_TUTEUR = ['suiviRencontres', 'evaluationNote', 'evaluationCommentaire'];
    /** États dans lesquels l'étudiant ne peut plus toucher à son dossier : saisie fermée, convention signée. */
    private const VERROUILLES_ETUDIANT = [EtatStageEnum::INCOMPLET, EtatStageEnum::CONVENTION_RECUE];

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        private readonly Security $security,
        private readonly StageAccess $access,
        private readonly StageNotifier $notifier,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof StageEtudiant) {
            return $this->persist->process($data, $operation, $uriVariables, $context);
        }

        $avant = ($context['previous_data'] ?? null) instanceof StageEtudiant ? $context['previous_data'] : null;
        $user = $this->security->getUser();
        $auteur = $user instanceof Etudiant || $user instanceof Personnel ? $user : null;

        $this->controler($data, $avant, $auteur, self::clesEnvoyees($context['request'] ?? null));

        $etatAvant = $avant?->getEtatStage();
        $tuteurAvant = $avant?->getTuteurUniversitaire();

        $resultat = $this->persist->process($data, $operation, $uriVariables, $context);

        $this->notifier->dossierEnregistre($data, $etatAvant, $tuteurAvant, $auteur);

        return $resultat;
    }

    /** @param list<string> $cles champs présents dans la requête */
    private function controler(StageEtudiant $stage, ?StageEtudiant $avant, Etudiant|Personnel|null $auteur, array $cles): void
    {
        $reference = $avant ?? $stage;
        if ($this->access->peutGerer($auteur, $reference)) {
            return;
        }

        if ($auteur instanceof Etudiant) {
            if ($avant !== null && \in_array($avant->getEtatStage(), self::VERROUILLES_ETUDIANT, true)) {
                throw new AccessDeniedHttpException($avant->getEtatStage() === EtatStageEnum::CONVENTION_RECUE
                    ? 'Votre convention est signée : contactez le service des stages pour toute modification (avenant).'
                    : 'La saisie de votre demande de convention n\'est pas ouverte.');
            }
            $interdits = array_values(array_intersect($cles, self::INTERDITS_ETUDIANT));
            if ($interdits !== []) {
                throw new AccessDeniedHttpException(sprintf('Champs réservés au service des stages : %s.', implode(', ', $interdits)));
            }
            $etat = $stage->getEtatStage();
            $etatsPermis = $avant === null ? [EtatStageEnum::AUTORISE, EtatStageEnum::DEPOSE] : [$avant->getEtatStage(), EtatStageEnum::DEPOSE];
            if (!\in_array($etat, $etatsPermis, true)) {
                throw new AccessDeniedHttpException('Un étudiant peut seulement déposer sa demande de convention.');
            }

            return;
        }

        if ($avant !== null && $this->access->estTuteur($auteur, $avant)) {
            $hors = array_values(array_diff($cles, self::AUTORISES_TUTEUR));
            if ($hors !== []) {
                throw new AccessDeniedHttpException(sprintf('Un tuteur peut seulement saisir le suivi et l\'évaluation (champs refusés : %s).', implode(', ', $hors)));
            }

            return;
        }

        throw new AccessDeniedHttpException('Vous ne pouvez pas modifier ce dossier de stage.');
    }

    /** @return list<string> */
    private static function clesEnvoyees(?Request $request): array
    {
        if ($request === null) {
            return [];
        }
        try {
            $corps = $request->toArray();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter(array_map('strval', array_keys($corps)), static fn (string $c) => !str_starts_with($c, '@') && $c !== 'id'));
    }
}
