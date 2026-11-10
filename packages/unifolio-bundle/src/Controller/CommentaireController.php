<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Security\TraceVoter;
use UnifolioBundle\Service\Evaluation\CommentaireService;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Fil de discussion d'une trace : visible de l'étudiant, de ses évaluateurs affectés et du référent. */
#[Route('/api/unifolio/traces/{uuid}/commentaires', requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
#[IsGranted('IS_AUTHENTICATED')]
class CommentaireController extends AbstractController
{
    public function __construct(
        private readonly TraceRepository $traces,
        private readonly CommentaireService $commentaires,
    ) {
    }

    #[Route('', name: 'unifolio_commentaires_liste', methods: ['GET'])]
    public function liste(string $uuid): JsonResponse
    {
        $trace = $this->trace($uuid);

        return $this->json($this->commentaires->lister($trace, $this->auteur()));
    }

    /** { contenu, rattachement?: id, parent?: uuid } */
    #[Route('', name: 'unifolio_commentaires_ajouter', methods: ['POST'])]
    public function ajouter(string $uuid, Request $request): JsonResponse
    {
        $trace = $this->trace($uuid);
        try {
            $d = $request->toArray();
        } catch (\Throwable) {
            throw UnifolioValidationException::un('Requête JSON invalide.');
        }
        $commentaire = $this->commentaires->ajouter(
            $trace,
            $this->auteur(),
            (string) ($d['contenu'] ?? ''),
            isset($d['rattachement']) ? (int) $d['rattachement'] : null,
            isset($d['parent']) ? (string) $d['parent'] : null,
        );

        return $this->json($commentaire, Response::HTTP_CREATED);
    }

    private function trace(string $uuid): \UnifolioBundle\Entity\Trace
    {
        $trace = $this->traces->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Trace introuvable.');
        $this->denyAccessUnlessGranted(TraceVoter::VOIR, $trace);

        return $trace;
    }

    private function auteur(): Etudiant|Personnel
    {
        $user = $this->getUser();
        if (!$user instanceof Etudiant && !$user instanceof Personnel) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
