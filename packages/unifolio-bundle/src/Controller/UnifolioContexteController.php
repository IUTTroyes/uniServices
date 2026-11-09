<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

/**
 * Contexte de l'utilisateur courant pour le front : capacités et scolarité BUT active.
 */
#[Route('/api/unifolio')]
class UnifolioContexteController extends AbstractController
{
    public function __construct(
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    #[Route('/contexte', name: 'unifolio_contexte', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function contexte(): JsonResponse
    {
        $user = $this->getUser();

        $capacites = [
            'acces' => $this->isGranted(UnifolioVoter::ACCES),
            'etudiant' => $this->isGranted(UnifolioVoter::ESPACE_ETUDIANT),
            'correcteur' => $this->isGranted(UnifolioVoter::CORRIGER),
            'referent' => $this->isGranted(UnifolioVoter::GERER),
            'moderateur' => $this->isGranted(UnifolioVoter::MODERER),
        ];

        $scolarite = null;
        if ($user instanceof Etudiant && $capacites['etudiant']) {
            $active = $this->perimetre->getScolariteActive($user);
            if ($active !== null) {
                $diplome = $this->perimetre->getDiplome($active);
                $annee = $this->perimetre->getAnneeFormation($active);
                $scolarite = [
                    'id' => $active->getId(),
                    'anneeUniversitaire' => $active->getAnneeUniversitaire()?->getLibelle(),
                    'diplome' => $diplome?->getLibelle(),
                    'annee' => $annee?->getLibelle(),
                ];
            }
        }

        return $this->json([
            'capacites' => $capacites,
            'scolarite' => $scolarite,
        ]);
    }
}
