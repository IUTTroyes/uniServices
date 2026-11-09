<?php

namespace UnifolioBundle\Controller;

use App\Entity\Structure\StructureDiplome;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\PortfolioModele;
use UnifolioBundle\Repository\PortfolioModeleRepository;
use UnifolioBundle\Security\ModeleVoter;
use UnifolioBundle\Security\ReferentContexte;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Portfolio\BlocRegistry;
use UnifolioBundle\Service\Portfolio\ModeleManager;
use UnifolioBundle\Service\Portfolio\ModeleNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Modèles de portfolio académique (référents) : pages et blocs imposés, verrouillables. */
#[Route('/api/unifolio/admin/modeles')]
#[IsGranted(UnifolioVoter::GERER)]
class AdminModeleController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PortfolioModeleRepository $modeles,
        private readonly ModeleManager $manager,
        private readonly ModeleNormalizer $normalizer,
        private readonly ReferentContexte $contexte,
        private readonly BlocRegistry $registre,
    ) {
    }

    #[Route('', name: 'unifolio_admin_modeles', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $modeles = $this->modeles->findVisibles($this->contexte->departement($this->personnel()));

        return $this->json(array_map(fn (PortfolioModele $m) => $this->normalizer->resume($m, $this->isGranted(ModeleVoter::MODIFIER, $m)), $modeles));
    }

    /** Schéma des blocs, pour l'éditeur de structure. */
    #[Route('/schema-blocs', name: 'unifolio_admin_modeles_schema', methods: ['GET'])]
    public function schema(): JsonResponse
    {
        return $this->json($this->registre->schema());
    }

    /** { libelle, description?, portee: departement|diplome|national, diplome? } */
    #[Route('', name: 'unifolio_admin_modeles_creer', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $d = $this->corps($request);
        [$departement, $diplome] = $this->portee($d);
        $modele = $this->manager->preparer($d, $departement, $diplome);
        $this->denyAccessUnlessGranted(ModeleVoter::MODIFIER, $modele);
        $this->manager->enregistrer($modele);

        return $this->json($this->normalizer->detail($modele, true), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'unifolio_admin_modeles_detail', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function detail(string $uuid): JsonResponse
    {
        $modele = $this->modele($uuid, ModeleVoter::VOIR);

        return $this->json($this->normalizer->detail($modele, $this->isGranted(ModeleVoter::MODIFIER, $modele)));
    }

    #[Route('/{uuid}', name: 'unifolio_admin_modeles_modifier', requirements: ['uuid' => self::UUID], methods: ['PATCH'])]
    public function modifier(string $uuid, Request $request): JsonResponse
    {
        $modele = $this->manager->modifier($this->modele($uuid, ModeleVoter::MODIFIER), $this->corps($request));

        return $this->json($this->normalizer->detail($modele, true));
    }

    /** { pages: [{ titre, verrouillee?, blocs, verrouilles? }] } */
    #[Route('/{uuid}/structure', name: 'unifolio_admin_modeles_structure', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function structure(string $uuid, Request $request): JsonResponse
    {
        $modele = $this->manager->definirStructure($this->modele($uuid, ModeleVoter::MODIFIER), $this->corps($request)['pages'] ?? null);

        return $this->json($this->normalizer->detail($modele, true));
    }

    /** { libelle?, portee? } — copie, pour adapter un modèle national à son département. */
    #[Route('/{uuid}/dupliquer', name: 'unifolio_admin_modeles_dupliquer', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function dupliquer(string $uuid, Request $request): JsonResponse
    {
        $source = $this->modele($uuid, ModeleVoter::VOIR);
        $d = $this->corps($request) + ['portee' => 'departement'];
        [$departement, $diplome] = $this->portee($d);
        $copie = $this->manager->preparerCopie($source, (string) ($d['libelle'] ?? ($source->getLibelle() . ' (copie)')), $departement, $diplome);
        $this->denyAccessUnlessGranted(ModeleVoter::MODIFIER, $copie);
        $this->manager->enregistrer($copie);

        return $this->json($this->normalizer->detail($copie, true), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'unifolio_admin_modeles_supprimer', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function supprimer(string $uuid): JsonResponse
    {
        $this->manager->supprimer($this->modele($uuid, ModeleVoter::MODIFIER));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array{0: ?\App\Entity\Structure\StructureDepartement, 1: ?StructureDiplome}
     */
    private function portee(array $d): array
    {
        $portee = (string) ($d['portee'] ?? 'departement');
        if ($portee === 'national') {
            return [null, null];
        }
        if ($portee === 'diplome') {
            $diplome = $this->em->find(StructureDiplome::class, (int) ($d['diplome'] ?? 0)) ?? throw UnifolioValidationException::un('Choisissez la formation.');

            return [$diplome->getDepartement(), $diplome];
        }
        $departement = $this->contexte->departementActif($this->personnel())
            ?? throw UnifolioValidationException::un('Aucun département actif : choisissez une formation comme portée.');

        return [$departement, null];
    }

    private function modele(string $uuid, string $droit): PortfolioModele
    {
        $modele = $this->modeles->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Modèle introuvable.');
        $this->denyAccessUnlessGranted($droit, $modele);

        return $modele;
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
