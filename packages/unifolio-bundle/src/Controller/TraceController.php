<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceMedia;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Security\TraceVoter;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Media\MediaStorageInterface;
use UnifolioBundle\Service\Trace\TraceManager;
use UnifolioBundle\Service\Trace\TraceNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

#[Route('/api/unifolio/traces')]
class TraceController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly TraceRepository $traces,
        private readonly TraceManager $manager,
        private readonly TraceNormalizer $normalizer,
        private readonly MediaStorageInterface $storage,
    ) {
    }

    #[Route('', name: 'unifolio_traces_liste', methods: ['GET'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function liste(Request $request): JsonResponse
    {
        $traces = $this->traces->findBibliotheque($this->etudiant(), [
            'scolarite' => $request->query->getInt('scolarite') ?: null,
            'statut' => StatutTraceEnum::tryFrom($request->query->getString('statut')),
            'competence' => $request->query->getInt('competence') ?: null,
            'q' => trim($request->query->getString('q')) ?: null,
        ]);

        return $this->json(array_map($this->normalizer->resume(...), $traces));
    }

    #[Route('', name: 'unifolio_traces_creer', methods: ['POST'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function creer(Request $request): JsonResponse
    {
        $trace = $this->manager->creer($this->etudiant(), $this->corps($request));

        return $this->json($this->normalizer->detail($trace), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', name: 'unifolio_traces_detail', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function detail(string $uuid): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::VOIR);

        return $this->json($this->normalizer->detail($trace));
    }

    #[Route('/{uuid}', name: 'unifolio_traces_modifier', requirements: ['uuid' => self::UUID], methods: ['PATCH'])]
    public function modifier(string $uuid, Request $request): JsonResponse
    {
        $trace = $this->manager->modifier($this->trace($uuid, TraceVoter::MODIFIER), $this->corps($request));

        return $this->json($this->normalizer->detail($trace));
    }

    #[Route('/{uuid}', name: 'unifolio_traces_supprimer', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    public function supprimer(string $uuid): JsonResponse
    {
        $this->manager->supprimer($this->trace($uuid, TraceVoter::SUPPRIMER));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/deposer', name: 'unifolio_traces_deposer', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function deposer(string $uuid): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::DEPOSER);
        $this->manager->deposer($trace);

        return $this->json($this->normalizer->detail($trace));
    }

    #[Route('/{uuid}/brouillon', name: 'unifolio_traces_brouillon', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function brouillon(string $uuid): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::MODIFIER);
        $this->manager->retirerEnBrouillon($trace);

        return $this->json($this->normalizer->detail($trace));
    }

    #[Route('/{uuid}/rattachements', name: 'unifolio_traces_rattachements', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function rattachements(string $uuid, Request $request): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::MODIFIER);
        $demandes = $this->corps($request)['rattachements'] ?? [];
        $this->manager->definirRattachements($trace, \is_array($demandes) ? array_values($demandes) : []);

        return $this->json($this->normalizer->detail($trace));
    }

    // ---------- Médias ----------

    /** Fichier : multipart (fichier, type facultatif, legende). Lien / intégration / texte / code : JSON. */
    #[Route('/{uuid}/medias', name: 'unifolio_traces_media_ajouter', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function ajouterMedia(string $uuid, Request $request): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::MODIFIER);
        $fichier = $request->files->get('fichier');
        $d = $fichier instanceof UploadedFile ? $request->request->all() : $this->corps($request);

        $type = TypeMediaEnum::tryFrom((string) ($d['type'] ?? ''));
        $legende = isset($d['legende']) ? (string) $d['legende'] : null;

        $media = match (true) {
            // Fichier : le type est déduit du format réel si non précisé.
            $fichier instanceof UploadedFile => $this->manager->ajouterFichier($trace, $fichier, $type, $legende),
            $type === null => throw UnifolioValidationException::un('Type de preuve inconnu.'),
            $type->requiertFichier() => throw UnifolioValidationException::un('Fichier manquant.'),
            \in_array($type, [TypeMediaEnum::LIEN, TypeMediaEnum::EMBED], true) => $this->manager->ajouterUrl($trace, $type, (string) ($d['url'] ?? ''), $legende),
            default => $this->manager->ajouterTexte($trace, $type, (string) ($d['contenu'] ?? ''), isset($d['langage']) ? (string) $d['langage'] : null, $legende),
        };

        return $this->json($this->normalizer->media($trace, $media), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}/medias/ordre', name: 'unifolio_traces_media_ordre', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function ordreMedias(string $uuid, Request $request): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::MODIFIER);
        $ids = $this->corps($request)['ids'] ?? [];
        $this->manager->reordonnerMedias($trace, \is_array($ids) ? $ids : []);

        return $this->json($this->normalizer->detail($trace));
    }

    #[Route('/{uuid}/medias/{id}', name: 'unifolio_traces_media_modifier', requirements: ['uuid' => self::UUID, 'id' => '\d+'], methods: ['PATCH'])]
    public function modifierMedia(string $uuid, int $id, Request $request): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::MODIFIER);
        $media = $this->manager->modifierMedia($this->media($trace, $id), $this->corps($request));

        return $this->json($this->normalizer->media($trace, $media));
    }

    #[Route('/{uuid}/medias/{id}', name: 'unifolio_traces_media_supprimer', requirements: ['uuid' => self::UUID, 'id' => '\d+'], methods: ['DELETE'])]
    public function supprimerMedia(string $uuid, int $id): JsonResponse
    {
        $trace = $this->trace($uuid, TraceVoter::MODIFIER);
        $this->manager->supprimerMedia($trace, $this->media($trace, $id));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/medias/{id}/fichier', name: 'unifolio_traces_media_fichier', requirements: ['uuid' => self::UUID, 'id' => '\d+'], methods: ['GET'])]
    public function fichier(string $uuid, int $id, Request $request): BinaryFileResponse
    {
        $trace = $this->trace($uuid, TraceVoter::VOIR);
        $media = $this->media($trace, $id);
        if (!$media->hasFichier() || !$this->storage->exists((string) $media->getChemin())) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($this->storage->cheminAbsolu((string) $media->getChemin()));
        $response->headers->set('Content-Type', (string) $media->getMimeType());
        // Les fichiers déposés par les étudiants ne doivent jamais exécuter de script (SVG, PDF…).
        $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPrivate();
        $response->setMaxAge(300);

        $inline = \in_array($media->getType(), [TypeMediaEnum::IMAGE, TypeMediaEnum::PDF, TypeMediaEnum::VIDEO], true)
            && !$request->query->getBoolean('telecharger');
        $response->setContentDisposition(
            $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            (string) $media->getNomOriginal(),
            'fichier'
        );

        return $response;
    }

    // ---------- Outils ----------

    private function trace(string $uuid, string $droit): Trace
    {
        $trace = $this->traces->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Trace introuvable.');
        $this->denyAccessUnlessGranted($droit, $trace);

        return $trace;
    }

    private function media(Trace $trace, int $id): TraceMedia
    {
        return $trace->getMediaParId($id) ?? throw $this->createNotFoundException('Preuve introuvable.');
    }

    /** @return array<string, mixed> */
    private function corps(Request $request): array
    {
        try {
            $donnees = $request->toArray();
        } catch (\Throwable) {
            throw UnifolioValidationException::un('Requête JSON invalide.');
        }

        return $donnees;
    }

    private function etudiant(): Etudiant
    {
        $user = $this->getUser();
        \assert($user instanceof Etudiant);

        return $user;
    }
}
