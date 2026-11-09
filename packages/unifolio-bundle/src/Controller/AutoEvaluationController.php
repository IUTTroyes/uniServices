<?php

namespace UnifolioBundle\Controller;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\AutoEvaluation;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Security\AutoEvaluationVoter;
use UnifolioBundle\Security\TraceVoter;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Evaluation\AutoEvaluationManager;
use UnifolioBundle\Service\Evaluation\AutoEvaluationNormalizer;
use UnifolioBundle\Service\Evaluation\BilanAnnee;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Trace\TraceNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

#[Route('/api/unifolio/auto-evaluations')]
class AutoEvaluationController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AutoEvaluationRepository $autoEvaluations,
        private readonly AutoEvaluationManager $manager,
        private readonly AutoEvaluationNormalizer $normalizer,
        private readonly BilanAnnee $bilan,
        private readonly UnifolioPerimetre $perimetre,
        private readonly TraceNormalizer $traces,
    ) {
    }

    /** Bilan d'une année : éléments du référentiel, traces associées, auto-évaluations. */
    #[Route('/bilan', name: 'unifolio_autoeval_bilan', methods: ['GET'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function bilan(Request $request): JsonResponse
    {
        $etudiant = $this->etudiant();
        $scolarite = $this->scolarite($etudiant, $request->query->getInt('scolarite'));

        try {
            $version = $this->manager->versionPour($scolarite);
            $granularite = $this->manager->granularitePour($scolarite);
        } catch (UnifolioValidationException $e) {
            return $this->json(['scolarite' => $this->traces->scolarite($scolarite), 'disponible' => false, 'message' => $e->getMessage(), 'elements' => []]);
        }

        return $this->json([
            'scolarite' => $this->traces->scolarite($scolarite),
            'disponible' => true,
            'granularite' => ['value' => $granularite->value, 'libelle' => $granularite->getLibelle()],
            'grille' => ['libelle' => $version->getGrille()->getLibelle(), 'version' => $version->getNumero()],
            'elements' => $this->bilan->elements($etudiant, $scolarite, $granularite),
        ]);
    }

    /** Ouvre une auto-évaluation : { rattachement: id } pour une trace, { scolarite, type, id } pour un bilan. */
    #[Route('', name: 'unifolio_autoeval_ouvrir', methods: ['POST'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function ouvrir(Request $request): JsonResponse
    {
        $d = $this->corps($request);
        $etudiant = $this->etudiant();

        if (isset($d['rattachement'])) {
            $rattachement = $this->em->find(TraceRattachement::class, (int) $d['rattachement']) ?? throw $this->createNotFoundException('Rattachement introuvable.');
            $this->denyAccessUnlessGranted(TraceVoter::VOIR, $rattachement->getTrace());
            if (!$rattachement->getTrace()->appartientA($etudiant)) {
                throw $this->createAccessDeniedException();
            }
            $auto = $this->manager->ouvrirPourRattachement($rattachement);
        } else {
            $scolarite = $this->scolarite($etudiant, (int) ($d['scolarite'] ?? 0));
            $auto = $this->manager->ouvrirBilan($etudiant, $scolarite, (string) ($d['type'] ?? ''), (int) ($d['id'] ?? 0));
        }

        return $this->json($this->normalizer->detail($auto));
    }

    #[Route('/{uuid}', name: 'unifolio_autoeval_detail', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function detail(string $uuid): JsonResponse
    {
        return $this->json($this->normalizer->detail($this->auto($uuid, AutoEvaluationVoter::VOIR)));
    }

    /** { reflexion?, reponses?: [{ critere, echelon?, valeur?, booleen?, texte? }] } */
    #[Route('/{uuid}', name: 'unifolio_autoeval_enregistrer', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function enregistrer(string $uuid, Request $request): JsonResponse
    {
        $auto = $this->manager->enregistrer($this->auto($uuid, AutoEvaluationVoter::MODIFIER), $this->corps($request));

        return $this->json($this->normalizer->detail($auto));
    }

    #[Route('/{uuid}/soumettre', name: 'unifolio_autoeval_soumettre', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function soumettre(string $uuid): JsonResponse
    {
        $auto = $this->auto($uuid, AutoEvaluationVoter::MODIFIER);
        $this->manager->soumettre($auto);

        return $this->json($this->normalizer->detail($auto));
    }

    #[Route('/{uuid}/reprendre', name: 'unifolio_autoeval_reprendre', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function reprendre(string $uuid): JsonResponse
    {
        $auto = $this->auto($uuid, AutoEvaluationVoter::REPRENDRE);
        $this->manager->reprendre($auto);

        return $this->json($this->normalizer->detail($auto));
    }

    #[Route('/{uuid}', name: 'unifolio_autoeval_supprimer', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function supprimer(string $uuid): JsonResponse
    {
        $this->manager->supprimer($this->auto($uuid, AutoEvaluationVoter::SUPPRIMER));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function auto(string $uuid, string $droit): AutoEvaluation
    {
        $auto = $this->autoEvaluations->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Auto-évaluation introuvable.');
        $this->denyAccessUnlessGranted($droit, $auto);

        return $auto;
    }

    private function scolarite(Etudiant $etudiant, int $id): EtudiantScolarite
    {
        $scolarite = $id > 0 ? $this->em->find(EtudiantScolarite::class, $id) : $this->perimetre->getScolariteActive($etudiant);
        if ($scolarite === null || $scolarite->getEtudiant()?->getId() !== $etudiant->getId() || !$this->perimetre->isScolariteEligible($scolarite)) {
            throw UnifolioValidationException::un('Année de BUT introuvable.');
        }

        return $scolarite;
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

    private function etudiant(): Etudiant
    {
        $user = $this->getUser();
        \assert($user instanceof Etudiant);

        return $user;
    }
}
