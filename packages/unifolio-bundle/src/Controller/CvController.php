<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Portfolio\CvManager;
use UnifolioBundle\Service\Portfolio\CvNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Le CV de l'étudiant, source des blocs « CV » de ses portfolios. */
#[Route('/api/unifolio/cv')]
#[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
class CvController extends AbstractController
{
    public function __construct(
        private readonly CvManager $manager,
        private readonly CvNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'unifolio_cv', methods: ['GET'])]
    public function lire(): JsonResponse
    {
        $cv = $this->manager->charger($this->etudiant());

        return $this->json($cv !== null ? $this->normalizer->document($cv) : $this->normalizer->vide());
    }

    /** Document complet : { poste, description, langues, softSkills, hardSkills, reseaux, experiences, formations } */
    #[Route('', name: 'unifolio_cv_enregistrer', methods: ['PUT'])]
    public function enregistrer(Request $request): JsonResponse
    {
        try {
            $d = $request->toArray();
        } catch (\Throwable) {
            throw UnifolioValidationException::un('Requête JSON invalide.');
        }

        return $this->json($this->normalizer->document($this->manager->enregistrer($this->etudiant(), $d)));
    }

    private function etudiant(): Etudiant
    {
        $user = $this->getUser();
        \assert($user instanceof Etudiant);

        return $user;
    }
}
