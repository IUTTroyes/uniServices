<?php

namespace FinanceBundle\Controller;

use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\ActionWorkflowEnum;
use FinanceBundle\Security\FinanceVoter;
use FinanceBundle\Service\FinanceDocumentStorage;
use FinanceBundle\Service\Notification\FinanceNotifier;
use FinanceBundle\Workflow\ActionRefuseeException;
use FinanceBundle\Workflow\FinanceWorkflow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Circuit d'une demande d'achat : POST /api/finance_bon_commandes/{id}/transitions/{action}
 * Corps JSON facultatif : { commentaire, date, factureFinale, typeReception, montantTtcConstate, … } selon l'action.
 * Constatation du service fait : formulaire multipart, mêmes champs + `bonsLivraison[]` (un ou plusieurs PDF, obligatoire).
 * Réponses : 200 (nouveau statut), 403 (droit), 404, 409 (statut incompatible), 422 (données invalides).
 */
class FinanceTransitionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FinanceWorkflow $workflow,
        private readonly FinanceNotifier $notifier,
        private readonly FinanceDocumentStorage $storage,
    ) {
    }

    #[Route('/api/finance_bon_commandes/{id}/transitions/{action}', name: 'api_finance_bon_commande_transition', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function transition(int $id, string $action, Request $request): JsonResponse
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande instanceof FinanceBonCommande) {
            throw new NotFoundHttpException('Demande / bon de commande non trouvé.');
        }
        $this->denyAccessUnlessGranted(FinanceVoter::BC_VOIR, $commande);
        $user = $this->getUser();
        \assert($user instanceof Personnel); // garanti par le voter

        $etape = ActionWorkflowEnum::tryFrom($action);
        if ($etape === null) {
            throw new NotFoundHttpException('Action inconnue.');
        }
        $formulaire = $request->files->count() > 0 || str_starts_with((string) $request->headers->get('Content-Type'), 'multipart/');
        if ($formulaire) {
            $donnees = $request->request->all();
            foreach (['prestationsConformes', 'factureFinale'] as $booleen) {
                if (\array_key_exists($booleen, $donnees)) {
                    $donnees[$booleen] = filter_var($donnees[$booleen], \FILTER_VALIDATE_BOOLEAN);
                }
            }
        } else {
            $donnees = $request->getContent() !== '' ? json_decode($request->getContent(), true) : [];
            if (!\is_array($donnees)) {
                return new JsonResponse(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Bons de livraison : enregistrés avant l'action (validés comme PDF), retirés si l'action échoue.
        $documents = [];
        if ($etape === ActionWorkflowEnum::CONSTATER_SERVICE_FAIT && $this->workflow->peut($user, $commande, $etape)) {
            $fichiers = $request->files->all()['bonsLivraison'] ?? [];
            try {
                $documents = $this->storage->enregistrerTous(\is_array($fichiers) ? $fichiers : [$fichiers]);
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            $this->workflow->appliquer($user, $commande, $etape, $donnees, $documents);
            $this->em->flush();
        } catch (ActionRefuseeException $e) {
            $this->oublier($documents);

            return new JsonResponse(['error' => $e->getMessage()], $e->porteSurLesDroits() ? Response::HTTP_FORBIDDEN : Response::HTTP_CONFLICT);
        } catch (\DomainException $e) {
            $this->oublier($documents);

            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            $this->oublier($documents);
            throw $e;
        }
        $this->notifier->apresAction($commande, $etape, $user);

        return new JsonResponse([
            'statut' => $commande->getStatut()->value,
            'statutLibelle' => $commande->getStatut()->getLabel(),
        ]);
    }

    /** @param list<array{chemin: string, nom: string}> $documents */
    private function oublier(array $documents): void
    {
        foreach ($documents as $document) {
            $this->storage->supprimer($document['chemin']);
        }
    }
}
