<?php

namespace StageBundle\Controller;

use App\Entity\Etablissement;
use App\Entity\Users\Personnel;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageEvaluationEntreprise;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Security\StageAccess;
use StageBundle\Service\Notification\StageNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Évaluation du stagiaire par son tuteur en entreprise.
 *
 * Côté établissement (connecté) : le tuteur universitaire ou un responsable envoie le lien, puis consulte l'évaluation.
 * Côté entreprise (sans compte) : le formulaire est ouvert par un jeton à usage unique, valable 30 jours ; seule son
 * empreinte est stockée. Un jeton inconnu, expiré ou déjà utilisé ne révèle rien du dossier.
 */
class StageEvaluationEntrepriseController extends AbstractController
{
    private const ETATS_ENVOI = [EtatStageEnum::CONVENTION_ENVOYEE, EtatStageEnum::CONVENTION_RECUE];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StageAccess $access,
        private readonly EmailService $emails,
        private readonly StageNotifier $notifier,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(URL_FRONTEND)%')]
        private readonly string $urlFrontend,
    ) {
    }

    // ---------- Côté établissement ----------

    #[Route('/api/stage_etudiants/{id}/evaluation-entreprise', name: 'api_stage_evaluation_entreprise', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function consulter(int $id): JsonResponse
    {
        $stage = $this->stageAutorise($id);
        $evaluation = $this->evaluation($stage);

        return $this->json([
            'statut' => $evaluation === null ? 'non_envoyee' : ($evaluation->estSoumise() ? 'soumise' : ($evaluation->estExpire() ? 'expiree' : 'envoyee')),
            'destinataire' => $this->destinataire($stage),
            'criteres' => \count($stage->getStagePeriode()?->getCriteresEvaluation() ?? []),
            'envoyeA' => $evaluation?->getEnvoyeA(),
            'envoyeLe' => $evaluation?->getEnvoyeLe()->format(\DATE_ATOM),
            'expireLe' => $evaluation?->getExpireLe()->format(\DATE_ATOM),
            'soumisLe' => $evaluation?->getSoumisLe()?->format(\DATE_ATOM),
            'evaluateurNom' => $evaluation?->getEvaluateurNom(),
            'reponses' => $evaluation?->getReponses() ?? [],
            'appreciation' => $evaluation?->getAppreciation(),
            'noteProposee' => $evaluation?->getNoteProposee(),
            'niveaux' => StageEvaluationEntreprise::NIVEAUX,
        ]);
    }

    /** Envoie (ou renvoie) le lien au tuteur en entreprise ; un renvoi rend l'ancien lien inutilisable. */
    #[Route('/api/stage_etudiants/{id}/evaluation-entreprise/envoyer', name: 'api_stage_evaluation_entreprise_envoyer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function envoyer(int $id): JsonResponse
    {
        $stage = $this->stageAutorise($id);
        if (!\in_array($stage->getEtatStage(), self::ETATS_ENVOI, true)) {
            return $this->json(['error' => 'L\'évaluation peut être demandée une fois la convention envoyée pour signature.'], Response::HTTP_CONFLICT);
        }
        if (\count($stage->getStagePeriode()?->getCriteresEvaluation() ?? []) === 0) {
            return $this->json(['error' => 'Aucun critère d\'évaluation n\'est défini pour cette période de stage.'], Response::HTTP_CONFLICT);
        }
        $destinataire = $this->destinataire($stage);
        if ($destinataire === null) {
            return $this->json(['error' => 'Aucune adresse e-mail valide pour le tuteur en entreprise.'], Response::HTTP_CONFLICT);
        }
        $evaluation = $this->evaluation($stage);
        if ($evaluation?->estSoumise()) {
            return $this->json(['error' => 'Le tuteur en entreprise a déjà rendu son évaluation.'], Response::HTTP_CONFLICT);
        }
        if ($evaluation === null) {
            $evaluation = new StageEvaluationEntreprise($stage);
            $this->em->persist($evaluation);
        }
        $jeton = $evaluation->emettreJeton($destinataire);
        $this->em->flush();

        $etab = $this->em->getRepository(Etablissement::class)->findOneBy(['isMain' => true]);
        $user = $this->getUser();
        try {
            $this->emails->send('stage.evaluation_entreprise', $destinataire, [
                'tuteur_nom' => $stage->getTuteur()?->getDisplay() ?? $stage->getEntreprise()?->getResponsable()?->getDisplay(),
                'etudiant_nom' => $stage->getEtudiant()?->getDisplay() ?? '',
                'periode' => $stage->getStagePeriode()?->getLibelle() ?? '',
                'entreprise' => $stage->getEntreprise()?->getRaisonSociale() ?? '',
                'etablissement' => $etab?->getLibelle() ?? '',
                'expediteur' => $user instanceof Personnel ? $user->getDisplay() : 'Le service des stages',
                'url' => rtrim($this->urlFrontend, '/').'/app/stage-evaluation/'.$jeton,
                'expire_le' => $evaluation->getExpireLe()->format('d/m/Y'),
            ], replyTo: $user instanceof Personnel ? $user->getMailUniv() : null);
        } catch (\Throwable $e) {
            $this->logger->error('Évaluation entreprise : e-mail non envoyé.', ['stage' => $stage->getId(), 'erreur' => $e->getMessage()]);

            return $this->json(['error' => 'Le lien a été créé mais l\'e-mail n\'a pas pu partir. Réessayez plus tard.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->consulter($id);
    }

    // ---------- Côté entreprise (public, par jeton) ----------

    #[Route('/api/stage/evaluation-entreprise/{jeton}', name: 'api_stage_evaluation_entreprise_public', requirements: ['jeton' => '[A-Za-z0-9_-]{20,100}'], methods: ['GET'])]
    public function formulaire(string $jeton): JsonResponse
    {
        $evaluation = $this->parJeton($jeton);
        if ($evaluation === null) {
            return $this->lienInvalide();
        }
        if ($evaluation->estSoumise()) {
            return $this->json(['statut' => 'soumise']);
        }
        if ($evaluation->estExpire()) {
            return $this->json(['statut' => 'expiree']);
        }
        $stage = $evaluation->getStageEtudiant();

        return $this->json([
            'statut' => 'ouverte',
            'etudiant' => $stage->getEtudiant()?->getDisplay() ?? '',
            'periode' => $stage->getStagePeriode()?->getLibelle() ?? '',
            'entreprise' => $stage->getEntreprise()?->getRaisonSociale() ?? '',
            'dates' => sprintf('du %s au %s', $stage->dateDebutStageFr(), $stage->dateFinStageFr()),
            'evaluateurNom' => $stage->getTuteur()?->getDisplay(),
            'criteres' => array_map(static fn ($c) => ['id' => $c->getId(), 'libelle' => $c->getLibelle(), 'description' => $c->getDescription()],
                array_values($stage->getStagePeriode()?->getCriteresEvaluation()->toArray() ?? [])),
            'niveaux' => StageEvaluationEntreprise::NIVEAUX,
            'expireLe' => $evaluation->getExpireLe()->format(\DATE_ATOM),
        ]);
    }

    #[Route('/api/stage/evaluation-entreprise/{jeton}', name: 'api_stage_evaluation_entreprise_soumettre', requirements: ['jeton' => '[A-Za-z0-9_-]{20,100}'], methods: ['POST'])]
    public function soumettre(string $jeton, Request $request): JsonResponse
    {
        $evaluation = $this->parJeton($jeton);
        if ($evaluation === null || $evaluation->estSoumise() || $evaluation->estExpire()) {
            return $this->lienInvalide();
        }
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Requête invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $stage = $evaluation->getStageEtudiant();
        $saisies = \is_array($data['reponses'] ?? null) ? $data['reponses'] : [];
        $reponses = [];
        $erreurs = [];
        foreach ($stage->getStagePeriode()?->getCriteresEvaluation() ?? [] as $critere) {
            $saisie = $saisies[(string) $critere->getId()] ?? $saisies[$critere->getId()] ?? null;
            $niveau = \is_array($saisie) ? (int) ($saisie['niveau'] ?? 0) : 0;
            if (!isset(StageEvaluationEntreprise::NIVEAUX[$niveau])) {
                $erreurs[] = sprintf('Choisissez un niveau pour « %s ».', $critere->getLibelle());
                continue;
            }
            $commentaire = \is_string($saisie['commentaire'] ?? null) ? mb_substr(trim($saisie['commentaire']), 0, 2000) : null;
            $reponses[] = ['critere' => (int) $critere->getId(), 'libelle' => (string) $critere->getLibelle(), 'niveau' => $niveau, 'commentaire' => $commentaire !== '' ? $commentaire : null];
        }
        $note = $data['noteProposee'] ?? null;
        if ($note !== null && $note !== '' && (!is_numeric($note) || (float) $note < 0 || (float) $note > 20)) {
            $erreurs[] = 'La note proposée doit être comprise entre 0 et 20.';
        }
        $nom = \is_string($data['evaluateurNom'] ?? null) ? mb_substr(trim($data['evaluateurNom']), 0, 150) : '';
        if ($nom === '') {
            $erreurs[] = 'Indiquez votre nom.';
        }
        if ($erreurs !== []) {
            return $this->json(['error' => implode("\n", $erreurs), 'erreurs' => $erreurs], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $appreciation = \is_string($data['appreciation'] ?? null) ? mb_substr(trim($data['appreciation']), 0, 5000) : null;
        $evaluation->soumettre($reponses, $appreciation !== '' ? $appreciation : null, $note === null || $note === '' ? null : round((float) $note, 2), $nom);
        $this->em->flush();
        $this->notifier->evaluationEntrepriseRecue($stage);

        return $this->json(['statut' => 'soumise']);
    }

    // ---------- Outils ----------

    private function stageAutorise(int $id): StageEtudiant
    {
        $stage = $this->em->getRepository(StageEtudiant::class)->find($id) ?? throw $this->createNotFoundException('Dossier de stage introuvable.');
        $user = $this->getUser();
        if (!$this->access->peutGerer($user, $stage) && !$this->access->estTuteur($user, $stage)) {
            throw $this->createAccessDeniedException();
        }

        return $stage;
    }

    private function evaluation(StageEtudiant $stage): ?StageEvaluationEntreprise
    {
        return $this->em->getRepository(StageEvaluationEntreprise::class)->findOneBy(['stageEtudiant' => $stage]);
    }

    private function parJeton(string $jeton): ?StageEvaluationEntreprise
    {
        return $this->em->getRepository(StageEvaluationEntreprise::class)->findOneBy(['jetonHash' => StageEvaluationEntreprise::empreinte($jeton)]);
    }

    /** Adresse du tuteur en entreprise, à défaut celle du signataire de l'organisme. */
    private function destinataire(StageEtudiant $stage): ?string
    {
        foreach ([$stage->getTuteur()?->getEmail(), $stage->getEntreprise()?->getResponsable()?->getEmail()] as $adresse) {
            if (\is_string($adresse) && filter_var(trim($adresse), \FILTER_VALIDATE_EMAIL)) {
                return trim($adresse);
            }
        }

        return null;
    }

    private function lienInvalide(): JsonResponse
    {
        return $this->json(['statut' => 'invalide', 'error' => 'Ce lien n\'est pas valide, a expiré ou a déjà été utilisé.'], Response::HTTP_NOT_FOUND);
    }
}
