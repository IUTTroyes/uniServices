<?php

namespace QuestionnaireBundle\Controller;

use App\Entity\Users\Personnel;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\Diffusion\DiffusionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Diffusion ciblée des résultats (équipe qui gère le questionnaire) :
 *
 * GET  /api/questionnaires/{uuid}/diffusion               état : sections, destinataires (pré-remplis au premier appel)
 * PUT  /api/questionnaires/{uuid}/diffusion               {"sections": {"<id>": [idPersonnel, …]}, "valider": true}
 * POST /api/questionnaires/{uuid}/diffusion/preremplir    ajoute les suggestions manquantes (prévisionnel, emploi du temps)
 * POST /api/questionnaires/{uuid}/resultats/publier       publie (questionnaire clôturé, diffusion validée)
 */
class QuestionnaireDiffusionController extends AbstractController
{
    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly DiffusionService $diffusion,
    ) {
    }

    #[Route('/api/questionnaires/{uuid}/diffusion', name: 'api_questionnaire_diffusion', methods: ['GET'])]
    public function etat(string $uuid): JsonResponse
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::MANAGE);
        if (!$q->isDiffusionInitialisee() && !$q->estBrouillon()) {
            $this->diffusion->preremplir($q);
        }

        return new JsonResponse($this->representation($q));
    }

    #[Route('/api/questionnaires/{uuid}/diffusion', name: 'api_questionnaire_diffusion_enregistrer', methods: ['PUT'])]
    public function enregistrer(string $uuid, Request $request): JsonResponse
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::MANAGE);
        $donnees = json_decode($request->getContent(), true);
        if (!\is_array($donnees) || !\is_array($donnees['sections'] ?? [])) {
            throw new UnprocessableEntityHttpException('Corps attendu : {"sections": {"<id de section>": [identifiants de personnels]}, "valider": bool}.');
        }
        $user = $this->getUser();
        $this->diffusion->enregistrer($q, $donnees['sections'] ?? [], (bool) ($donnees['valider'] ?? false), $user instanceof Personnel ? $user : null);

        return new JsonResponse($this->representation($q));
    }

    #[Route('/api/questionnaires/{uuid}/diffusion/preremplir', name: 'api_questionnaire_diffusion_preremplir', methods: ['POST'])]
    public function preremplir(string $uuid): JsonResponse
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::MANAGE);
        $ajouts = $this->diffusion->preremplir($q);

        return new JsonResponse(['ajouts' => $ajouts] + $this->representation($q));
    }

    #[Route('/api/questionnaires/{uuid}/resultats/publier', name: 'api_questionnaire_resultats_publier', methods: ['POST'])]
    public function publier(string $uuid): JsonResponse
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::MANAGE);
        $notifies = $this->diffusion->publier($q);

        return new JsonResponse(['notifies' => $notifies] + $this->representation($q));
    }

    /** @return array<string, mixed> */
    private function representation(Questionnaire $q): array
    {
        $sections = [];
        foreach ($this->diffusion->sections($q) as $section) {
            $sections[] = [
                'id' => $section->getId(),
                'titre' => $section->getTitleSnapshot(),
                'type' => $section->getRepeatSectionItemType(),
                'enseignement' => $section->getEnseignement() ? [
                    'id' => $section->getEnseignement()->getId(),
                    'libelle' => $section->getEnseignement()->getLibelle(),
                    'code' => $section->getEnseignement()->getCodeEnseignement(),
                ] : null,
                'intervenant' => $section->getPersonnelCible()?->getDisplay(),
                'destinataires' => array_values(array_map(static fn ($d) => [
                    'id' => $d->getPersonnel()->getId(),
                    'nom' => $d->getPersonnel()->getDisplay(),
                    'origine' => $d->getOrigine(),
                    'notifie' => $d->getNotifieLe()?->format(\DateTimeInterface::ATOM),
                ], $section->getDiffusions()->toArray())),
            ];
        }
        $fin = $this->diffusion->finRelecture($q);

        return [
            'etape' => $q->getEtapeResultats(),
            'validee' => $q->getDiffusionValideeLe()?->format(\DateTimeInterface::ATOM),
            'valideePar' => $q->getDiffusionValideePar()?->getDisplay(),
            'clotureLe' => $q->getClotureLe()?->format(\DateTimeInterface::ATOM),
            'finRelecture' => $fin?->format(\DateTimeInterface::ATOM),
            'publieLe' => $q->getResultatsPubliesLe()?->format(\DateTimeInterface::ATOM),
            'sections' => $sections,
        ];
    }
}
