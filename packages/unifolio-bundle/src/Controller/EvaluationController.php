<?php

namespace UnifolioBundle\Controller;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Repository\CampagneRepository;
use UnifolioBundle\Repository\EvaluationRepository;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Security\CampagneVoter;
use UnifolioBundle\Security\EvaluationVoter;
use UnifolioBundle\Security\TraceVoter;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Campagne\CampagneEtudiants;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;
use UnifolioBundle\Service\Campagne\CampagneResolver;
use UnifolioBundle\Service\Evaluation\AutoEvaluationManager;
use UnifolioBundle\Service\Evaluation\BilanAnnee;
use UnifolioBundle\Service\Evaluation\EvaluationLot;
use UnifolioBundle\Service\Evaluation\EvaluationManager;
use UnifolioBundle\Service\Evaluation\EvaluationNormalizer;
use UnifolioBundle\Service\Evaluation\FileEvaluation;
use UnifolioBundle\Service\Trace\TraceNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Espace des évaluateurs : file d'évaluation, suivi d'un étudiant, évaluation d'une trace ou d'un bilan. */
#[Route('/api/unifolio/evaluations')]
#[IsGranted(UnifolioVoter::CORRIGER)]
class EvaluationController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EvaluationRepository $evaluations,
        private readonly CampagneRepository $campagnes,
        private readonly CampagneAffectationRepository $affectations,
        private readonly TraceRepository $traces,
        private readonly EvaluationManager $manager,
        private readonly EvaluationNormalizer $normalizer,
        private readonly FileEvaluation $file,
        private readonly BilanAnnee $bilan,
        private readonly CampagneEtudiants $etudiants,
        private readonly CampagneResolver $resolver,
        private readonly CampagneNormalizer $campagneNormalizer,
        private readonly AutoEvaluationManager $autoEvaluationManager,
        private readonly TraceNormalizer $traceNormalizer,
        private readonly EvaluationLot $lot,
    ) {
    }

    #[Route('/file', name: 'unifolio_eval_file', methods: ['GET'])]
    public function aEvaluer(): JsonResponse
    {
        return $this->json($this->file->pour($this->personnel()));
    }

    /** Année d'un étudiant dans une campagne : éléments du référentiel, traces, auto-évaluations, évaluations. */
    #[Route('/etudiants/{campagne}/{etudiant}', name: 'unifolio_eval_etudiant', requirements: ['campagne' => self::UUID, 'etudiant' => '\d+'], methods: ['GET'])]
    public function etudiant(string $campagne, int $etudiant): JsonResponse
    {
        $campagne = $this->campagnes->findOneByUuid($campagne) ?? throw $this->createNotFoundException('Campagne introuvable.');
        $user = $this->personnel();
        if (!$this->affectations->existe($campagne, $user, $this->em->getReference(\App\Entity\Users\Etudiant::class, $etudiant)) && !$this->isGranted(CampagneVoter::GERER, $campagne)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas chargé(e) de cet étudiant.');
        }
        $scolarite = $this->etudiants->scolarites($campagne)[$etudiant] ?? throw $this->createNotFoundException('Cet étudiant ne fait pas partie de la campagne.');
        $e = $scolarite->getEtudiant();

        $base = [
            'campagne' => $this->campagneNormalizer->resume($campagne),
            'etudiant' => ['id' => $e->getId(), 'nom' => $e->getNom(), 'prenom' => $e->getPrenom()],
            'scolarite' => $this->traceNormalizer->scolarite($scolarite),
        ];
        try {
            $version = $this->autoEvaluationManager->versionPour($scolarite);
            $granularite = $this->autoEvaluationManager->granularitePour($scolarite);
        } catch (UnifolioValidationException $ex) {
            return $this->json($base + ['disponible' => false, 'message' => $ex->getMessage(), 'elements' => []]);
        }

        return $this->json($base + [
            'disponible' => true,
            'granularite' => ['value' => $granularite->value, 'libelle' => $granularite->getLibelle()],
            'grille' => ['libelle' => $version->getGrille()->getLibelle(), 'version' => $version->getNumero()],
            'elements' => $this->bilan->elements($e, $scolarite, $granularite, $user),
        ]);
    }

    /** Trace à évaluer : contenu complet, ses rattachements et, pour chacun, mon évaluation et celles des autres. */
    #[Route('/traces/{uuid}', name: 'unifolio_eval_trace', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function trace(string $uuid): JsonResponse
    {
        $trace = $this->traces->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Trace introuvable.');
        $this->denyAccessUnlessGranted(TraceVoter::VOIR, $trace);
        $user = $this->personnel();

        $parRattachement = [];
        foreach ($this->evaluations->findParTrace($trace) as $e) {
            $cle = $e->getRattachement()->getId();
            if ($e->estDe($user)) {
                $parRattachement[$cle]['mienne'] = $this->normalizer->resume($e);
            } elseif ($e->getStatut() === StatutEvaluationEnum::RENDUE) {
                $parRattachement[$cle]['autres'][] = $this->normalizer->resume($e);
            }
        }
        $campagne = $trace->getScolarite() !== null ? $this->resolver->pourScolarite($trace->getScolarite()) : null;

        return $this->json([
            'trace' => $this->traceNormalizer->detail($trace),
            'etudiant' => ['id' => $trace->getEtudiant()->getId(), 'nom' => $trace->getEtudiant()->getDisplay()],
            'campagne' => $campagne !== null ? $this->campagneNormalizer->resume($campagne) : null,
            'evaluations' => (object) array_map(static fn (array $g) => ['mienne' => $g['mienne'] ?? null, 'autres' => $g['autres'] ?? []], $parRattachement),
        ]);
    }

    /** Ouvre (ou retrouve) mon évaluation : { rattachement } pour une trace, { scolarite, type, id } pour un bilan. */
    #[Route('', name: 'unifolio_eval_ouvrir', methods: ['POST'])]
    public function ouvrir(Request $request): JsonResponse
    {
        $d = $this->corps($request);
        $user = $this->personnel();

        if (isset($d['rattachement'])) {
            $rattachement = $this->em->find(TraceRattachement::class, (int) $d['rattachement']) ?? throw $this->createNotFoundException('Rattachement introuvable.');
            $this->denyAccessUnlessGranted(TraceVoter::VOIR, $rattachement->getTrace());
            $evaluation = $this->manager->ouvrirPourRattachement($user, $rattachement);
        } else {
            $scolarite = $this->em->find(EtudiantScolarite::class, (int) ($d['scolarite'] ?? 0)) ?? throw $this->createNotFoundException('Année introuvable.');
            $evaluation = $this->manager->ouvrirBilan($user, $scolarite, (string) ($d['type'] ?? ''), (int) ($d['id'] ?? 0));
        }

        return $this->json($this->normalizer->detail($evaluation));
    }

    #[Route('/{uuid}', name: 'unifolio_eval_detail', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function detail(string $uuid): JsonResponse
    {
        return $this->json($this->normalizer->detail($this->evaluation($uuid, EvaluationVoter::VOIR)));
    }

    /** { appreciation?, reponses?: [{ critere, echelon?, valeur?, booleen?, texte? }] } */
    #[Route('/{uuid}', name: 'unifolio_eval_enregistrer', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function enregistrer(string $uuid, Request $request): JsonResponse
    {
        $e = $this->manager->enregistrer($this->evaluation($uuid, EvaluationVoter::MODIFIER), $this->corps($request));

        return $this->json($this->normalizer->detail($e));
    }

    /** { visibleEtudiant?: bool } — communiquée à l'étudiant par défaut. */
    #[Route('/{uuid}/rendre', name: 'unifolio_eval_rendre', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function rendre(string $uuid, Request $request): JsonResponse
    {
        $e = $this->evaluation($uuid, EvaluationVoter::MODIFIER);
        $d = $this->corps($request);
        $this->manager->rendre($e, (bool) ($d['visibleEtudiant'] ?? true));

        return $this->json($this->normalizer->detail($e));
    }

    /** { motif } */
    #[Route('/{uuid}/revision', name: 'unifolio_eval_revision', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function revision(string $uuid, Request $request): JsonResponse
    {
        $e = $this->evaluation($uuid, EvaluationVoter::MODIFIER);
        $this->manager->demanderRevision($e, (string) ($this->corps($request)['motif'] ?? ''));

        return $this->json($this->normalizer->detail($e));
    }

    #[Route('/{uuid}/rouvrir', name: 'unifolio_eval_rouvrir', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function rouvrir(string $uuid): JsonResponse
    {
        $e = $this->manager->rouvrir($this->evaluation($uuid, EvaluationVoter::MODIFIER));

        return $this->json($this->normalizer->detail($e));
    }

    #[Route('/{uuid}', name: 'unifolio_eval_supprimer', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function supprimer(string $uuid): JsonResponse
    {
        $this->manager->supprimer($this->evaluation($uuid, EvaluationVoter::MODIFIER));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    // ---------- Évaluation en lot : tous les points d'une trace ou de l'année, en une fois ----------

    #[Route('/lot/trace/{uuid}', name: 'unifolio_eval_lot_trace', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function lotTrace(string $uuid): JsonResponse
    {
        return $this->json($this->lot->charger($this->personnel(), $this->porteeTrace($uuid)));
    }

    /** { items: [{ cle, appreciation?, reponses? }] } — crée les évaluations des points renseignés. */
    #[Route('/lot/trace/{uuid}', name: 'unifolio_eval_lot_trace_enregistrer', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function lotTraceEnregistrer(string $uuid, Request $request): JsonResponse
    {
        $portee = $this->porteeTrace($uuid);
        $items = $this->corps($request)['items'] ?? [];
        $this->lot->enregistrer($this->personnel(), $portee, \is_array($items) ? array_values($items) : []);

        return $this->json($this->lot->charger($this->personnel(), $portee));
    }

    /** { visibleEtudiant?: bool } */
    #[Route('/lot/trace/{uuid}/rendre', name: 'unifolio_eval_lot_trace_rendre', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function lotTraceRendre(string $uuid, Request $request): JsonResponse
    {
        $portee = $this->porteeTrace($uuid);
        $nb = $this->lot->rendre($this->personnel(), $portee, (bool) ($this->corps($request)['visibleEtudiant'] ?? true));

        return $this->json($this->lot->charger($this->personnel(), $portee) + ['nbRendues' => $nb]);
    }

    /** { cles?: [string] } — sans clés, rouvre tout. */
    #[Route('/lot/trace/{uuid}/rouvrir', name: 'unifolio_eval_lot_trace_rouvrir', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function lotTraceRouvrir(string $uuid, Request $request): JsonResponse
    {
        $portee = $this->porteeTrace($uuid);
        $cles = $this->corps($request)['cles'] ?? null;
        $nb = $this->lot->rouvrir($this->personnel(), $portee, \is_array($cles) ? array_map('strval', $cles) : null);

        return $this->json($this->lot->charger($this->personnel(), $portee) + ['nbRouvertes' => $nb]);
    }

    /** { motif } — demande à l'étudiant de réviser la trace (un seul e-mail). */
    #[Route('/lot/trace/{uuid}/revision', name: 'unifolio_eval_lot_trace_revision', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function lotTraceRevision(string $uuid, Request $request): JsonResponse
    {
        $trace = $this->traces->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Trace introuvable.');
        $this->denyAccessUnlessGranted(TraceVoter::VOIR, $trace);
        $this->lot->demanderRevision($this->personnel(), $trace, (string) ($this->corps($request)['motif'] ?? ''));

        return $this->json($this->lot->charger($this->personnel(), $this->lot->porteeTrace($trace)));
    }

    #[Route('/lot/bilans/{campagne}/{etudiant}', name: 'unifolio_eval_lot_bilans', requirements: ['campagne' => self::UUID, 'etudiant' => '\d+'], methods: ['GET'])]
    public function lotBilans(string $campagne, int $etudiant): JsonResponse
    {
        return $this->json($this->lot->charger($this->personnel(), $this->porteeBilans($campagne, $etudiant)));
    }

    #[Route('/lot/bilans/{campagne}/{etudiant}', name: 'unifolio_eval_lot_bilans_enregistrer', requirements: ['campagne' => self::UUID, 'etudiant' => '\d+'], methods: ['PUT'])]
    public function lotBilansEnregistrer(string $campagne, int $etudiant, Request $request): JsonResponse
    {
        $portee = $this->porteeBilans($campagne, $etudiant);
        $items = $this->corps($request)['items'] ?? [];
        $this->lot->enregistrer($this->personnel(), $portee, \is_array($items) ? array_values($items) : []);

        return $this->json($this->lot->charger($this->personnel(), $portee));
    }

    #[Route('/lot/bilans/{campagne}/{etudiant}/rendre', name: 'unifolio_eval_lot_bilans_rendre', requirements: ['campagne' => self::UUID, 'etudiant' => '\d+'], methods: ['POST'])]
    public function lotBilansRendre(string $campagne, int $etudiant, Request $request): JsonResponse
    {
        $portee = $this->porteeBilans($campagne, $etudiant);
        $nb = $this->lot->rendre($this->personnel(), $portee, (bool) ($this->corps($request)['visibleEtudiant'] ?? true));

        return $this->json($this->lot->charger($this->personnel(), $portee) + ['nbRendues' => $nb]);
    }

    #[Route('/lot/bilans/{campagne}/{etudiant}/rouvrir', name: 'unifolio_eval_lot_bilans_rouvrir', requirements: ['campagne' => self::UUID, 'etudiant' => '\d+'], methods: ['POST'])]
    public function lotBilansRouvrir(string $campagne, int $etudiant, Request $request): JsonResponse
    {
        $portee = $this->porteeBilans($campagne, $etudiant);
        $cles = $this->corps($request)['cles'] ?? null;
        $nb = $this->lot->rouvrir($this->personnel(), $portee, \is_array($cles) ? array_map('strval', $cles) : null);

        return $this->json($this->lot->charger($this->personnel(), $portee) + ['nbRouvertes' => $nb]);
    }

    /** @return array{trace: ?\UnifolioBundle\Entity\Trace, scolarite: EtudiantScolarite, cibles: list<\UnifolioBundle\Service\Evaluation\CibleLot>} */
    private function porteeTrace(string $uuid): array
    {
        $trace = $this->traces->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Trace introuvable.');
        $this->denyAccessUnlessGranted(TraceVoter::VOIR, $trace);

        return $this->lot->porteeTrace($trace);
    }

    /** @return array{trace: ?\UnifolioBundle\Entity\Trace, scolarite: EtudiantScolarite, cibles: list<\UnifolioBundle\Service\Evaluation\CibleLot>} */
    private function porteeBilans(string $campagneUuid, int $etudiantId): array
    {
        $campagne = $this->campagnes->findOneByUuid($campagneUuid) ?? throw $this->createNotFoundException('Campagne introuvable.');
        if (!$this->affectations->existe($campagne, $this->personnel(), $this->em->getReference(\App\Entity\Users\Etudiant::class, $etudiantId)) && !$this->isGranted(CampagneVoter::GERER, $campagne)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas chargé(e) de cet étudiant.');
        }
        $scolarite = $this->etudiants->scolarites($campagne)[$etudiantId] ?? throw $this->createNotFoundException('Cet étudiant ne fait pas partie de la campagne.');

        return $this->lot->porteeBilans($scolarite);
    }

    private function evaluation(string $uuid, string $droit): Evaluation
    {
        $e = $this->evaluations->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Évaluation introuvable.');
        $this->denyAccessUnlessGranted($droit, $e);

        return $e;
    }

    /** @return array<string, mixed> */
    private function corps(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            throw UnifolioValidationException::un('Requête JSON invalide.');
        }
    }

    private function personnel(): Personnel
    {
        $user = $this->getUser();
        \assert($user instanceof Personnel);

        return $user;
    }
}
