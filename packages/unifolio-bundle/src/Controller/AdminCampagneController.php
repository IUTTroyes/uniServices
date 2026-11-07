<?php

namespace UnifolioBundle\Controller;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Repository\CampagneRepository;
use UnifolioBundle\Security\CampagneVoter;
use UnifolioBundle\Security\ReferentContexte;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Campagne\AffectationManager;
use UnifolioBundle\Service\Campagne\CampagneEtudiants;
use UnifolioBundle\Service\Campagne\CampagneManager;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Administration des campagnes d'évaluation et des affectations d'évaluateurs (référents). */
#[Route('/api/unifolio/admin/campagnes')]
#[IsGranted(UnifolioVoter::GERER)]
class AdminCampagneController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CampagneRepository $campagnes,
        private readonly CampagneAffectationRepository $affectations,
        private readonly CampagneManager $manager,
        private readonly AffectationManager $affectationManager,
        private readonly CampagneEtudiants $etudiants,
        private readonly CampagneNormalizer $normalizer,
        private readonly ReferentContexte $contexte,
    ) {
    }

    #[Route('', name: 'unifolio_admin_campagnes', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $campagnes = array_filter(
            $this->campagnes->findPourDepartement($this->contexte->departement($this->personnel())),
            fn (Campagne $c) => $this->isGranted(CampagneVoter::GERER, $c)
        );

        return $this->json(array_map($this->avecCompteurs(...), array_values($campagnes)));
    }

    /** Formations BUT, années universitaires et années de BUT proposées à la création. */
    #[Route('/options', name: 'unifolio_admin_campagnes_options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $departement = $this->contexte->departement($this->personnel());
        $diplomes = $this->em->getRepository(StructureDiplome::class)->findBy($departement !== null ? ['departement' => $departement] : [], ['libelle' => 'ASC']);

        $formations = [];
        foreach ($diplomes as $diplome) {
            if ($diplome->getTypeDiplome()?->isApc() !== true || !$this->isGranted(CampagneVoter::GERER, $diplome)) {
                continue;
            }
            $annees = [];
            foreach ($diplome->getPns() as $pn) {
                foreach ($pn->getAnnees() as $annee) {
                    $annees[] = ['id' => $annee->getId(), 'libelle' => $annee->getLibelle(), 'ordre' => $annee->getOrdre(), 'anneeUniversitaire' => $pn->getAnneeUniversitaire()?->getId()];
                }
            }
            usort($annees, static fn (array $a, array $b) => [$a['ordre'], $a['id']] <=> [$b['ordre'], $b['id']]);
            $formations[] = ['id' => $diplome->getId(), 'libelle' => $diplome->getLibelle(), 'parcours' => $diplome->getParcours()?->getLibelle(), 'departement' => $diplome->getDepartement()?->getLibelle(), 'annees' => $annees];
        }

        $repo = $this->em->getRepository(StructureAnneeUniversitaire::class);

        return $this->json([
            'formations' => $formations,
            'anneesUniversitaires' => array_map(
                static fn (StructureAnneeUniversitaire $a) => ['id' => $a->getId(), 'libelle' => $a->getLibelle()],
                $repo->findBy([], ['annee' => 'DESC'])
            ),
            'anneeCourante' => ($repo->findOneBy(['actif' => true]) ?? $repo->findOneBy([], ['annee' => 'DESC']))?->getId(),
        ]);
    }

    #[Route('', name: 'unifolio_admin_campagnes_creer', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $d = $this->corps($request);
        $diplome = $this->em->find(StructureDiplome::class, (int) ($d['diplome'] ?? 0)) ?? throw UnifolioValidationException::un('Choisissez la formation.');
        $this->denyAccessUnlessGranted(CampagneVoter::GERER, $diplome);

        $campagne = $this->manager->enregistrer($this->manager->preparer($diplome, $d));

        return $this->json($this->avecCompteurs($campagne), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'unifolio_admin_campagnes_detail', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function detail(string $uuid): JsonResponse
    {
        return $this->json($this->avecCompteurs($this->campagne($uuid)));
    }

    #[Route('/{uuid}', name: 'unifolio_admin_campagnes_modifier', requirements: ['uuid' => self::UUID], methods: ['PATCH'])]
    public function modifier(string $uuid, Request $request): JsonResponse
    {
        $campagne = $this->manager->modifier($this->campagne($uuid), $this->corps($request));

        return $this->json($this->avecCompteurs($campagne));
    }

    #[Route('/{uuid}', name: 'unifolio_admin_campagnes_supprimer', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function supprimer(string $uuid): JsonResponse
    {
        $this->manager->supprimer($this->campagne($uuid));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/cloturer', name: 'unifolio_admin_campagnes_cloturer', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function cloturer(string $uuid): JsonResponse
    {
        return $this->json($this->avecCompteurs($this->manager->cloturer($this->campagne($uuid))));
    }

    // ---------- Affectations ----------

    #[Route('/{uuid}/affectations', name: 'unifolio_admin_campagnes_affectations', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function affectationsListe(string $uuid): JsonResponse
    {
        $campagne = $this->campagne($uuid);
        $scolarites = $this->etudiants->scolarites($campagne);

        $evaluateursParEtudiant = [];
        $nbParEvaluateur = [];
        $evaluateurs = [];
        foreach ($this->affectations->findParCampagne($campagne) as $a) {
            $p = $a->getEvaluateur();
            $evaluateursParEtudiant[$a->getEtudiant()->getId()][] = $p->getId();
            $nbParEvaluateur[$p->getId()] = ($nbParEvaluateur[$p->getId()] ?? 0) + 1;
            $evaluateurs[$p->getId()] = ['id' => $p->getId(), 'nom' => $p->getDisplay()];
        }
        foreach ($evaluateurs as $id => &$e) {
            $e['nbEtudiants'] = $nbParEvaluateur[$id];
        }
        unset($e);

        $groupes = [];
        $lignes = [];
        foreach ($scolarites as $id => $scolarite) {
            $nomsGroupes = [];
            foreach ($scolarite->getScolariteSemestre() as $ess) {
                foreach ($ess->getGroupes() as $g) {
                    $nomsGroupes[$g->getId()] = $g->getLibelle();
                    $groupes[$g->getId()] ??= ['id' => $g->getId(), 'libelle' => $g->getLibelle(), 'nbEtudiants' => 0];
                    ++$groupes[$g->getId()]['nbEtudiants'];
                }
            }
            $etudiant = $scolarite->getEtudiant();
            $lignes[] = [
                'id' => $id,
                'nom' => $etudiant->getNom(),
                'prenom' => $etudiant->getPrenom(),
                'annee' => $scolarite->getAnnee()[0]?->getLibelle() ?? null,
                'groupes' => array_values($nomsGroupes),
                'evaluateurs' => $evaluateursParEtudiant[$id] ?? [],
            ];
        }
        usort($groupes, static fn (array $a, array $b) => strnatcmp($a['libelle'], $b['libelle']));

        $candidats = [];
        foreach ($campagne->getDiplome()->getDepartement()?->getDepartementPersonnels() ?? [] as $dp) {
            $p = $dp->getPersonnel();
            if ($p !== null) {
                $candidats[$p->getId()] = ['id' => $p->getId(), 'nom' => $p->getDisplay()];
            }
        }
        uasort($candidats, static fn (array $a, array $b) => strcasecmp($a['nom'], $b['nom']));

        return $this->json([
            'campagne' => $this->normalizer->resume($campagne),
            'etudiants' => $lignes,
            'evaluateurs' => array_values($evaluateurs),
            'groupes' => $groupes,
            'candidats' => array_values($candidats),
        ]);
    }

    /** { evaluateur: id, etudiants?: [ids], groupe?: id } ; sans étudiants ni groupe = tous les étudiants de la campagne. */
    #[Route('/{uuid}/affectations', name: 'unifolio_admin_campagnes_affecter', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function affecter(string $uuid, Request $request): JsonResponse
    {
        $campagne = $this->campagne($uuid);
        $d = $this->corps($request);
        $evaluateur = $this->em->find(Personnel::class, (int) ($d['evaluateur'] ?? 0)) ?? throw UnifolioValidationException::un('Choisissez l\'enseignant.');
        $groupe = !empty($d['groupe']) ? ($this->em->find(StructureGroupe::class, (int) $d['groupe']) ?? throw UnifolioValidationException::un('Groupe introuvable.')) : null;

        $crees = $this->affectationManager->affecter($campagne, $evaluateur, isset($d['etudiants']) && \is_array($d['etudiants']) ? $d['etudiants'] : null, $groupe);

        return $this->json(['crees' => $crees]);
    }

    /** { evaluateur: id, etudiants?: [ids] } */
    #[Route('/{uuid}/affectations', name: 'unifolio_admin_campagnes_desaffecter', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function desaffecter(string $uuid, Request $request): JsonResponse
    {
        $campagne = $this->campagne($uuid);
        $d = $this->corps($request);
        $evaluateur = $this->em->find(Personnel::class, (int) ($d['evaluateur'] ?? 0)) ?? throw UnifolioValidationException::un('Choisissez l\'enseignant.');

        $retirees = $this->affectationManager->retirer($campagne, $evaluateur, isset($d['etudiants']) && \is_array($d['etudiants']) ? $d['etudiants'] : null);

        return $this->json(['retirees' => $retirees]);
    }

    // ---------- Outils ----------

    /** @return array<string, mixed> */
    private function avecCompteurs(Campagne $c): array
    {
        $affectes = [];
        foreach ($this->affectations->findParCampagne($c) as $a) {
            $affectes[$a->getEtudiant()->getId()] = true;
        }
        $evaluations = $this->em->getRepository(Evaluation::class);

        return $this->normalizer->resume($c) + [
            'nbEtudiants' => \count($this->etudiants->scolarites($c)),
            'nbAffectes' => \count($affectes),
            'nbEvaluations' => $evaluations->count(['campagne' => $c]),
        ];
    }

    private function campagne(string $uuid): Campagne
    {
        $campagne = $this->campagnes->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Campagne introuvable.');
        $this->denyAccessUnlessGranted(CampagneVoter::GERER, $campagne);

        return $campagne;
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
