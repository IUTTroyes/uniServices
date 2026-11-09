<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Repository\CampagneRepository;
use UnifolioBundle\Security\CampagneVoter;
use UnifolioBundle\Security\ReferentContexte;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;
use UnifolioBundle\Service\Suivi\SuiviCampagne;
use UnifolioBundle\Service\Suivi\SuiviEtudiant;

/** Suivi : progression de l'étudiant sur son cursus, tableau de bord de promo pour les enseignants et référents. */
#[Route('/api/unifolio')]
class SuiviController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly CampagneRepository $campagnes,
        private readonly CampagneAffectationRepository $affectations,
        private readonly CampagneNormalizer $campagneNormalizer,
        private readonly ReferentContexte $contexte,
        private readonly SuiviCampagne $suiviCampagne,
        private readonly SuiviEtudiant $suiviEtudiant,
    ) {
    }

    // ---------- Étudiant ----------

    #[Route('/progression', name: 'unifolio_progression', methods: ['GET'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function progression(): JsonResponse
    {
        $etudiant = $this->getUser();
        \assert($etudiant instanceof Etudiant);

        return $this->json($this->suiviEtudiant->pour($etudiant));
    }

    // ---------- Enseignants et référents ----------

    /** Campagnes suivies : celles où l'on a des étudiants affectés, plus (référents) celles des formations administrées. */
    #[Route('/suivi/campagnes', name: 'unifolio_suivi_campagnes', methods: ['GET'])]
    #[IsGranted(UnifolioVoter::CORRIGER)]
    public function campagnes(): JsonResponse
    {
        $user = $this->personnel();
        $liste = [];
        foreach ($this->affectations->findParEvaluateur($user) as $a) {
            $liste[$a->getCampagne()->getId()] = $a->getCampagne();
        }
        foreach ($this->campagnes->findPourDepartement($this->contexte->isSuperAdmin($user) ? null : $this->contexte->departement($user)) as $c) {
            if ($this->isGranted(CampagneVoter::GERER, $c)) {
                $liste[$c->getId()] = $c;
            }
        }
        usort($liste, static fn (Campagne $a, Campagne $b) => [$a->isCloturee(), $b->getAnneeUniversitaire()->getLibelle()] <=> [$b->isCloturee(), $a->getAnneeUniversitaire()->getLibelle()]);

        return $this->json(array_map(
            fn (Campagne $c) => $this->campagneNormalizer->resume($c) + ['perimetre' => $this->isGranted(CampagneVoter::GERER, $c) ? 'promo' : 'affectes'],
            array_values($liste)
        ));
    }

    #[Route('/suivi/campagnes/{uuid}', name: 'unifolio_suivi_campagne', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    #[IsGranted(UnifolioVoter::CORRIGER)]
    public function campagne(string $uuid): JsonResponse
    {
        [$campagne, $ids] = $this->portee($uuid);

        return $this->json($this->suiviCampagne->construire($campagne, $ids));
    }

    #[Route('/suivi/campagnes/{uuid}/export.csv', name: 'unifolio_suivi_export', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    #[IsGranted(UnifolioVoter::CORRIGER)]
    public function export(string $uuid): Response
    {
        [$campagne, $ids] = $this->portee($uuid);
        $reponse = new Response($this->suiviCampagne->csv($this->suiviCampagne->construire($campagne, $ids)));
        $reponse->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $reponse->headers->set('Content-Disposition', 'attachment; filename="suivi-'.substr($campagne->getUuidString(), 0, 8).'.csv"');
        $reponse->headers->set('Cache-Control', 'private, no-store');

        return $reponse;
    }

    /**
     * Périmètre : toute la campagne pour son référent, sinon les seuls étudiants affectés à l'enseignant.
     *
     * @return array{0: Campagne, 1: list<int>|null}
     */
    private function portee(string $uuid): array
    {
        $campagne = $this->campagnes->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Campagne introuvable.');
        if ($this->isGranted(CampagneVoter::GERER, $campagne)) {
            return [$campagne, null];
        }
        $ids = array_map(static fn ($a) => (int) $a->getEtudiant()->getId(), $this->affectations->findParEvaluateur($this->personnel(), $campagne));
        if ($ids === []) {
            throw $this->createAccessDeniedException('Aucun étudiant de cette campagne ne vous est affecté.');
        }

        return [$campagne, $ids];
    }

    private function personnel(): Personnel
    {
        $user = $this->getUser();
        \assert($user instanceof Personnel);

        return $user;
    }
}
