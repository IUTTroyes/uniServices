<?php

namespace UnifolioBundle\Controller;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;
use UnifolioBundle\Service\Trace\TraceNormalizer;

#[Route('/api/unifolio')]
#[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
class ReferentielController extends AbstractController
{
    public function __construct(
        private readonly UnifolioPerimetre $perimetre,
        private readonly ReferentielAnnee $referentiel,
        private readonly TraceNormalizer $normalizer,
    ) {
    }

    /** Années de BUT de l'étudiant (filtre de la bibliothèque, choix de l'année d'une trace). */
    #[Route('/scolarites', name: 'unifolio_scolarites', methods: ['GET'])]
    public function scolarites(): JsonResponse
    {
        return $this->json(array_map(
            fn (EtudiantScolarite $s) => $this->normalizer->scolarite($s),
            $this->scolaritesBut($this->etudiant())
        ));
    }

    /** Référentiel de l'année : compétences → niveaux → apprentissages critiques. */
    #[Route('/referentiel', name: 'unifolio_referentiel', methods: ['GET'])]
    public function referentiel(Request $request): JsonResponse
    {
        $etudiant = $this->etudiant();
        $id = $request->query->getInt('scolarite');
        $scolarite = null;
        foreach ($this->scolaritesBut($etudiant) as $s) {
            if ($id === 0 ? $s->isActif() : $s->getId() === $id) {
                $scolarite = $s;
                break;
            }
        }
        if ($scolarite === null) {
            throw $this->createNotFoundException('Année de BUT introuvable.');
        }

        return $this->json([
            'scolarite' => $this->normalizer->scolarite($scolarite),
            'competences' => $this->referentiel->arbre($scolarite),
        ]);
    }

    /** @return EtudiantScolarite[] de la plus récente à la plus ancienne */
    private function scolaritesBut(Etudiant $etudiant): array
    {
        $scolarites = array_filter(
            $etudiant->getScolarites()->toArray(),
            fn (EtudiantScolarite $s) => $this->perimetre->isScolariteEligible($s)
        );
        usort($scolarites, static fn (EtudiantScolarite $a, EtudiantScolarite $b) => $b->getOrdre() <=> $a->getOrdre());

        return array_values($scolarites);
    }

    private function etudiant(): Etudiant
    {
        $user = $this->getUser();
        \assert($user instanceof Etudiant);

        return $user;
    }
}
