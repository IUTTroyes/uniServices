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
use StageBundle\Service\StageEntrepriseResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use ApiPlatform\Validator\Exception\ValidationException;
use StageBundle\Service\StageReglesLegales;

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
    /**
     * Champs réservés au service des stages : un étudiant ne peut pas les écrire.
     * (Les dates de traitement et le rapport ne sont écrits par aucun client : ils ne sont pas dans le groupe d'écriture.)
     */
    private const INTERDITS_ETUDIANT = ['tuteurUniversitaire', 'evaluationNote', 'evaluationCommentaire', 'suiviRencontres', 'motifRefus'];
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
        private readonly StageEntrepriseResolver $entreprises,
        private readonly StageReglesLegales $regles,
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

        $this->controlerTransition($data, $etatAvant, $auteur);

        $this->entreprises->resoudre($data, $avant?->getEntreprise(), $avant?->getTuteur());
        $this->horodater($data, $etatAvant, $auteur);

        $resultat = $this->persist->process($data, $operation, $uriVariables, $context);

        $this->notifier->dossierEnregistre($data, $etatAvant, $tuteurAvant, $auteur);

        return $resultat;
    }

    private function controlerTransition(StageEtudiant $stage, ?EtatStageEnum $etatAvant, Etudiant|Personnel|null $auteur): void
    {
        $etat = $stage->getEtatStage();
        if ($etatAvant !== null && !$etatAvant->peutPasserA($etat)) {
            throw new UnprocessableEntityHttpException(sprintf('Un dossier « %s » ne peut pas passer à « %s ».', $etatAvant->getLibelle(), $etat->getLibelle()));
        }
        // Le motif ne vaut que pour un refus : il disparaît dès que le dossier change d'état.
        if ($etat !== EtatStageEnum::REFUS && $etat !== $etatAvant) {
            $stage->setMotifRefus(null);
        }

        // Règles légales : au dépôt (et à chaque nouvelle version de l'étudiant) et à la validation.
        if (\in_array($etat, [EtatStageEnum::DEPOSE, EtatStageEnum::VALIDE], true) && ($etat !== $etatAvant || $auteur instanceof Etudiant)) {
            $erreurs = $this->regles->verifier($stage);
            if ($erreurs !== []) {
                $violations = new ConstraintViolationList();
                foreach ($erreurs as ['champ' => $champ, 'message' => $message]) {
                    $violations->add(new ConstraintViolation($message, $message, [], $stage, $champ, null));
                }
                throw new ValidationException($violations);
            }
        }
    }

    /** Dates de traitement posées par le serveur au changement de statut (le client ne peut plus les écrire). */
    private function horodater(StageEtudiant $stage, ?EtatStageEnum $etatAvant, Etudiant|Personnel|null $auteur): void
    {
        $etat = $stage->getEtatStage();
        $maintenant = new \DateTime();
        // Un nouveau dépôt de l'étudiant (même s'il était déjà « déposé ») met à jour la date de dépôt.
        if ($etat === EtatStageEnum::DEPOSE && ($etat !== $etatAvant || $auteur instanceof Etudiant)) {
            $stage->setDateDepotFormulaire($maintenant);
        }
        if ($etat === $etatAvant) {
            return;
        }
        match ($etat) {
            EtatStageEnum::AUTORISE => $stage->setDateAutorise($maintenant),
            EtatStageEnum::VALIDE => $stage->setDateValidation($maintenant),
            EtatStageEnum::CONVENTION_IMPRIMEE => $stage->setDateImprime($maintenant),
            EtatStageEnum::CONVENTION_ENVOYEE => $stage->setDateConventionEnvoyee($maintenant)->setDateImprime($stage->getDateImprime() ?? $maintenant),
            EtatStageEnum::CONVENTION_RECUE => $stage->setDateConventionRecu($maintenant),
            default => null,
        };
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
