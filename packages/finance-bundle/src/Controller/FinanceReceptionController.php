<?php

namespace FinanceBundle\Controller;

use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceReception;
use FinanceBundle\Entity\FinanceReceptionDocument;
use FinanceBundle\Enum\ActionWorkflowEnum;
use FinanceBundle\Security\FinanceVoter;
use FinanceBundle\Service\FinanceDocumentStorage;
use FinanceBundle\Service\Notification\FinanceNotifier;
use FinanceBundle\Workflow\ActionRefuseeException;
use FinanceBundle\Workflow\FinanceWorkflow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Service fait, second temps : bons de livraison d'une réception et visa du service financier.
 *  - POST   /api/finance_receptions/{id}/bons-livraison        ajoute un ou plusieurs PDF (`fichiers[]`) ;
 *  - GET    /api/finance_receptions/{id}/bons-livraison/{doc}  lit un PDF (personnes qui voient la demande) ;
 *  - DELETE /api/finance_receptions/{id}/bons-livraison/{doc}  retire un PDF ;
 *  - POST   /api/finance_receptions/{id}/viser                 visa (service financier), { numerosMigo?: [] }.
 * Ajout et retrait : tant que la réception n'est pas visée (cf. FinanceWorkflow::peutGererBonsLivraison).
 */
class FinanceReceptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FinanceWorkflow $workflow,
        private readonly FinanceDocumentStorage $storage,
        private readonly FinanceNotifier $notifier,
    ) {
    }

    #[Route('/api/finance_receptions/{id}/bons-livraison', name: 'api_finance_reception_bons_livraison_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ajouter(int $id, Request $request): JsonResponse
    {
        $reception = $this->reception($id);
        $this->gerable($reception);
        $user = $this->getUser();
        \assert($user instanceof Personnel);

        $fichiers = $request->files->all()['fichiers'] ?? [];
        $fichiers = \is_array($fichiers) ? $fichiers : [$fichiers];
        if ($fichiers === []) {
            return new JsonResponse(['error' => 'Aucun fichier fourni.'], Response::HTTP_BAD_REQUEST);
        }
        if ($reception->getDocuments()->count() + \count($fichiers) > 2 * FinanceDocumentStorage::FICHIERS_MAX) {
            return new JsonResponse(['error' => sprintf('%d bons de livraison au plus par réception.', 2 * FinanceDocumentStorage::FICHIERS_MAX)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            $documents = $this->storage->enregistrerTous($fichiers);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        foreach ($documents as $document) {
            $reception->addDocument((new FinanceReceptionDocument())->setChemin($document['chemin'])->setNomFichier($document['nom'])->setDeposePar($user));
        }
        try {
            $this->em->flush();
        } catch (\Throwable $e) {
            foreach ($documents as $document) {
                $this->storage->supprimer($document['chemin']);
            }
            throw $e;
        }

        return new JsonResponse(['documents' => $reception->getDocuments()->count()], Response::HTTP_CREATED);
    }

    #[Route('/api/finance_receptions/{id}/bons-livraison/{document}', name: 'api_finance_reception_bon_livraison_lire', requirements: ['id' => '\d+', 'document' => '\d+'], methods: ['GET'])]
    public function lire(int $id, int $document): Response
    {
        $reception = $this->reception($id);
        $this->denyAccessUnlessGranted(FinanceVoter::BC_VOIR, $reception->getBonCommande());
        $doc = $this->document($reception, $document);

        $fichier = $this->storage->localiser($doc->getChemin());
        if ($fichier === null) {
            throw new NotFoundHttpException('Le fichier de ce bon de livraison est introuvable.');
        }
        $reponse = new BinaryFileResponse($fichier, Response::HTTP_OK, ['Content-Type' => FinanceDocumentStorage::MIME]);
        $reponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $doc->getNomFichier(), sprintf('bon_livraison_%d.pdf', $doc->getId()));
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');

        return $reponse;
    }

    #[Route('/api/finance_receptions/{id}/bons-livraison/{document}', name: 'api_finance_reception_bon_livraison_retirer', requirements: ['id' => '\d+', 'document' => '\d+'], methods: ['DELETE'])]
    public function retirer(int $id, int $document): Response
    {
        $reception = $this->reception($id);
        $this->gerable($reception);
        $doc = $this->document($reception, $document);
        $reception->removeDocument($doc); // fichier supprimé après la suppression en base (FinanceDocumentCleanupListener)
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/finance_receptions/{id}/viser', name: 'api_finance_reception_viser', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function viser(int $id, Request $request): JsonResponse
    {
        $reception = $this->reception($id);
        $commande = $reception->getBonCommande();
        $this->denyAccessUnlessGranted(FinanceVoter::BC_VOIR, $commande);
        $user = $this->getUser();
        \assert($user instanceof Personnel);

        $donnees = $request->getContent() !== '' ? json_decode($request->getContent(), true) : [];
        if (!\is_array($donnees) || (\array_key_exists('numerosMigo', $donnees) && !\is_array($donnees['numerosMigo']))) {
            return new JsonResponse(['error' => 'Corps JSON invalide : { "numerosMigo": ["…"] } attendu.'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $certifie = $this->workflow->viserReception($user, $reception, \array_key_exists('numerosMigo', $donnees) ? array_values($donnees['numerosMigo']) : null);
        } catch (ActionRefuseeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->porteSurLesDroits() ? Response::HTTP_FORBIDDEN : Response::HTTP_CONFLICT);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->em->flush();
        if ($certifie) {
            $this->notifier->apresAction($commande, ActionWorkflowEnum::CERTIFIER_SERVICE_FAIT, $user);
        }

        return new JsonResponse([
            'certifie' => $certifie,
            'statut' => $commande->getStatut()->value,
            'statutLibelle' => $commande->getStatut()->getLabel(),
        ]);
    }

    private function reception(int $id): FinanceReception
    {
        $reception = $this->em->getRepository(FinanceReception::class)->find($id);
        if (!$reception instanceof FinanceReception || $reception->getBonCommande() === null) {
            throw new NotFoundHttpException('Réception non trouvée.');
        }

        return $reception;
    }

    private function document(FinanceReception $reception, int $id): FinanceReceptionDocument
    {
        foreach ($reception->getDocuments() as $document) {
            if ($document->getId() === $id) {
                return $document;
            }
        }
        throw new NotFoundHttpException('Bon de livraison non trouvé.');
    }

    private function gerable(FinanceReception $reception): void
    {
        $this->denyAccessUnlessGranted(FinanceVoter::BC_VOIR, $reception->getBonCommande());
        if (!$this->workflow->peutGererBonsLivraison($this->getUser(), $reception)) {
            throw new AccessDeniedHttpException($reception->isVisee()
                ? 'Cette réception est visée : ses bons de livraison ne se modifient plus.'
                : 'Vous ne pouvez pas modifier les bons de livraison de cette réception.');
        }
    }
}
