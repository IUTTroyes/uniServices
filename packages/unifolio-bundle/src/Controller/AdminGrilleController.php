<?php

namespace UnifolioBundle\Controller;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Enum\AgregationEnum;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Repository\GrilleRepository;
use UnifolioBundle\Repository\ParametrageFormationRepository;
use UnifolioBundle\Security\GrilleVoter;
use UnifolioBundle\Security\ReferentContexte;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Grille\GrilleManager;
use UnifolioBundle\Service\Grille\GrilleNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Administration UniFolio (référents) : grilles d'évaluation et paramétrage des formations BUT. */
#[Route('/api/unifolio/admin')]
#[IsGranted(UnifolioVoter::GERER)]
class AdminGrilleController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GrilleRepository $grilles,
        private readonly ParametrageFormationRepository $parametrages,
        private readonly GrilleManager $manager,
        private readonly GrilleNormalizer $normalizer,
        private readonly ReferentContexte $contexte,
    ) {
    }

    // ---------- Grilles ----------

    #[Route('/grilles', name: 'unifolio_admin_grilles', methods: ['GET'])]
    public function grilles(): JsonResponse
    {
        $grilles = $this->grilles->findVisibles($this->contexte->departement($this->personnel()));

        return $this->json(array_map(
            fn (Grille $g) => $this->normalizer->resume($g, $this->isGranted(GrilleVoter::MODIFIER, $g)),
            array_values(array_filter($grilles, fn (Grille $g) => $this->isGranted(GrilleVoter::VOIR, $g)))
        ));
    }

    /** { libelle, granularite, portee: departement|diplome|type_diplome, diplome?: id, description? } */
    #[Route('/grilles', name: 'unifolio_admin_grilles_creer', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $d = $this->corps($request);
        [$type, $departement, $diplome] = $this->portee($d);
        $grille = $this->manager->preparer($d, $type, $departement, $diplome);
        $this->denyAccessUnlessGranted(GrilleVoter::MODIFIER, $grille);
        $this->manager->enregistrer($grille);

        return $this->json($this->normalizer->detail($grille, true), Response::HTTP_CREATED);
    }

    #[Route('/grilles/{uuid}', name: 'unifolio_admin_grilles_detail', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function detail(string $uuid): JsonResponse
    {
        $grille = $this->grille($uuid, GrilleVoter::VOIR);

        return $this->json($this->normalizer->detail($grille, $this->isGranted(GrilleVoter::MODIFIER, $grille)));
    }

    #[Route('/grilles/{uuid}', name: 'unifolio_admin_grilles_modifier', requirements: ['uuid' => self::UUID], methods: ['PATCH'])]
    public function modifier(string $uuid, Request $request): JsonResponse
    {
        $grille = $this->manager->modifier($this->grille($uuid, GrilleVoter::MODIFIER), $this->corps($request));

        return $this->json($this->normalizer->detail($grille, true));
    }

    #[Route('/grilles/{uuid}', name: 'unifolio_admin_grilles_supprimer', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function supprimer(string $uuid): JsonResponse
    {
        $this->manager->supprimer($this->grille($uuid, GrilleVoter::MODIFIER));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/grilles/{uuid}/brouillon', name: 'unifolio_admin_grilles_brouillon_creer', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function nouvelleVersion(string $uuid): JsonResponse
    {
        $grille = $this->grille($uuid, GrilleVoter::MODIFIER);
        $this->manager->nouvelleVersion($grille);

        return $this->json($this->normalizer->detail($grille, true));
    }

    #[Route('/grilles/{uuid}/brouillon', name: 'unifolio_admin_grilles_brouillon_enregistrer', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function enregistrerBrouillon(string $uuid, Request $request): JsonResponse
    {
        $grille = $this->grille($uuid, GrilleVoter::MODIFIER);
        $this->manager->enregistrerBrouillon($grille, $this->corps($request));

        return $this->json($this->normalizer->detail($grille, true));
    }

    #[Route('/grilles/{uuid}/brouillon', name: 'unifolio_admin_grilles_brouillon_abandonner', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function abandonnerBrouillon(string $uuid): JsonResponse
    {
        $grille = $this->grille($uuid, GrilleVoter::MODIFIER);
        $this->manager->abandonnerBrouillon($grille);

        return $this->json($this->normalizer->detail($grille, true));
    }

    #[Route('/grilles/{uuid}/publier', name: 'unifolio_admin_grilles_publier', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function publier(string $uuid): JsonResponse
    {
        $grille = $this->grille($uuid, GrilleVoter::MODIFIER);
        $this->manager->publier($grille);

        return $this->json($this->normalizer->detail($grille, true));
    }

    /** Copie une grille (souvent nationale) pour l'adapter : { libelle?, portee, diplome? } */
    #[Route('/grilles/{uuid}/dupliquer', name: 'unifolio_admin_grilles_dupliquer', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function dupliquer(string $uuid, Request $request): JsonResponse
    {
        $source = $this->grille($uuid, GrilleVoter::VOIR);
        $d = $this->corps($request) + ['portee' => Grille::PORTEE_DEPARTEMENT];
        [, $departement, $diplome] = $this->portee($d, $source->getTypeDiplome());
        $copie = $this->manager->preparerCopie($source, (string) ($d['libelle'] ?? ($source->getLibelle() . ' (copie)')), $departement, $diplome);
        $this->denyAccessUnlessGranted(GrilleVoter::MODIFIER, $copie);
        $this->manager->enregistrer($copie);

        return $this->json($this->normalizer->detail($copie, true), Response::HTTP_CREATED);
    }

    // ---------- Paramétrage des formations ----------

    #[Route('/formations', name: 'unifolio_admin_formations', methods: ['GET'])]
    public function formations(): JsonResponse
    {
        $anneeCourante = $this->anneeCourante();
        $departement = $this->contexte->departement($this->personnel());

        $criteres = $departement !== null ? ['departement' => $departement] : [];
        $diplomes = array_filter(
            $this->em->getRepository(StructureDiplome::class)->findBy($criteres, ['libelle' => 'ASC']),
            fn (StructureDiplome $d) => $d->getTypeDiplome()?->isApc() === true && $this->isGranted(GrilleVoter::PARAMETRER_FORMATION, $d)
        );

        $formations = [];
        foreach ($diplomes as $diplome) {
            $enVigueur = $anneeCourante !== null ? $this->parametrages->findApplicable($diplome, $anneeCourante) : null;
            $formations[] = [
                'id' => $diplome->getId(),
                'libelle' => $diplome->getLibelle(),
                'sigle' => $diplome->getSigle(),
                'parcours' => $diplome->getParcours()?->getLibelle(),
                'departement' => $diplome->getDepartement()?->getLibelle(),
                'parametrage' => $this->normalizer->parametrage($enVigueur),
                'historique' => array_map($this->normalizer->parametrage(...), $this->parametrages->findHistorique($diplome)),
                'grilles' => array_map(
                    fn (Grille $g) => ['uuid' => $g->getUuidString(), 'libelle' => $g->getLibelle(), 'granularite' => $g->getGranularite()->value, 'portee' => $g->getPortee()],
                    array_values(array_filter($this->grilles->findApplicables($diplome), static fn (Grille $g) => $g->hasVersionPubliee()))
                ),
            ];
        }

        return $this->json([
            'anneeCourante' => $anneeCourante !== null ? ['id' => $anneeCourante->getId(), 'libelle' => $anneeCourante->getLibelle()] : null,
            'anneesUniversitaires' => array_map(
                static fn (StructureAnneeUniversitaire $a) => ['id' => $a->getId(), 'libelle' => $a->getLibelle()],
                $this->em->getRepository(StructureAnneeUniversitaire::class)->findBy([], ['annee' => 'DESC'])
            ),
            'granularites' => array_map(static fn (GranulariteEnum $g) => ['value' => $g->value, 'libelle' => $g->getLibelle()], GranulariteEnum::cases()),
            'agregations' => array_map(static fn (AgregationEnum $a) => ['value' => $a->value, 'libelle' => $a->getLibelle()], AgregationEnum::cases()),
            'formations' => $formations,
        ]);
    }

    /** { granularite, agregation?, grille?: uuid|null, applicableDepuis?: id année universitaire (défaut : année en cours) } */
    #[Route('/formations/{id}', name: 'unifolio_admin_formations_parametrer', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function parametrer(int $id, Request $request): JsonResponse
    {
        $diplome = $this->em->find(StructureDiplome::class, $id) ?? throw $this->createNotFoundException('Formation introuvable.');
        $this->denyAccessUnlessGranted(GrilleVoter::PARAMETRER_FORMATION, $diplome);

        $d = $this->corps($request);
        $annee = !empty($d['applicableDepuis'])
            ? $this->em->find(StructureAnneeUniversitaire::class, (int) $d['applicableDepuis'])
            : $this->anneeCourante();
        if ($annee === null) {
            throw UnifolioValidationException::un('Année universitaire introuvable.');
        }

        $parametrage = $this->manager->parametrer($diplome, $annee, $d);
        if ($parametrage->getGrille() !== null) {
            $this->denyAccessUnlessGranted(GrilleVoter::VOIR, $parametrage->getGrille());
        }

        return $this->json($this->normalizer->parametrage($parametrage));
    }

    // ---------- Outils ----------

    /**
     * @param array<string, mixed> $d
     *
     * @return array{0: StructureTypeDiplome, 1: ?\App\Entity\Structure\StructureDepartement, 2: ?StructureDiplome}
     */
    private function portee(array $d, ?StructureTypeDiplome $type = null): array
    {
        $portee = (string) ($d['portee'] ?? Grille::PORTEE_DEPARTEMENT);
        $personnel = $this->personnel();

        if ($portee === Grille::PORTEE_DIPLOME) {
            $diplome = $this->em->find(StructureDiplome::class, (int) ($d['diplome'] ?? 0)) ?? throw UnifolioValidationException::un('Choisissez le diplôme.');

            return [$diplome->getTypeDiplome() ?? throw UnifolioValidationException::un('Diplôme sans type.'), $diplome->getDepartement(), $diplome];
        }

        $type ??= $this->typeBut();
        if ($portee === Grille::PORTEE_TYPE_DIPLOME) {
            return [$type, null, null];
        }

        $departement = $this->contexte->departementActif($personnel)
            ?? throw UnifolioValidationException::un('Aucun département actif : choisissez un diplôme comme portée.');

        return [$type, $departement, null];
    }

    private function typeBut(): StructureTypeDiplome
    {
        $types = $this->em->getRepository(StructureTypeDiplome::class)->findBy(['apc' => true]);

        return $types[0] ?? throw UnifolioValidationException::un('Aucun type de diplôme APC n\'est défini.');
    }

    private function anneeCourante(): ?StructureAnneeUniversitaire
    {
        $repo = $this->em->getRepository(StructureAnneeUniversitaire::class);

        return $repo->findOneBy(['actif' => true]) ?? $repo->findOneBy([], ['annee' => 'DESC']);
    }

    private function grille(string $uuid, string $droit): Grille
    {
        $grille = $this->grilles->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Grille introuvable.');
        $this->denyAccessUnlessGranted($droit, $grille);

        return $grille;
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
