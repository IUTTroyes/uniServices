<?php

namespace FinanceBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Users\Personnel;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum;
use FinanceBundle\Workflow\FinanceWorkflow;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Création et modification d'une demande d'achat (le circuit lui-même passe par FinanceTransitionController).
 *  - Les clés envoyées sont contrôlées : chaque profil ne peut modifier que les champs permis au statut actuel
 *    (FinanceWorkflow::champsModifiables) ; statut, avis, dates de visa et signataires ne s'écrivent jamais ici.
 *  - Création : le demandeur est la personne connectée, la demande part en brouillon, datée du jour.
 *  - Montants : le montant manquant (HT ou TTC) est calculé à partir du taux de TVA, sans écraser un montant saisi.
 *
 * @implements ProcessorInterface<mixed, mixed>
 */
final class FinanceBonCommandeProcessor implements ProcessorInterface
{
    /** Clés JSON-LD sans effet, tolérées dans le corps. */
    private const CLES_NEUTRES = ['@context', '@id', '@type', 'id'];

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        private readonly Security $security,
        private readonly FinanceWorkflow $workflow,
        private readonly RequestStack $requests,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof FinanceBonCommande) {
            return $this->persist->process($data, $operation, $uriVariables, $context);
        }
        $user = $this->security->getUser();
        if (!$user instanceof Personnel) {
            throw new AccessDeniedHttpException();
        }

        $creation = $operation instanceof Post;
        $permis = $creation
            ? FinanceWorkflow::champsCreation()
            : $this->workflow->champsModifiables($user, $context['previous_data'] ?? $data);
        $interdits = array_values(array_diff($this->clesEnvoyees($context), $permis, self::CLES_NEUTRES));
        if ($interdits !== []) {
            throw new AccessDeniedHttpException(sprintf(
                'Vous ne pouvez pas modifier %s à ce stade de la demande.',
                implode(', ', array_map(static fn (string $c): string => '« '.$c.' »', $interdits))
            ));
        }

        if ($creation) {
            $data->setDemandeur($user)
                ->setStatut(StatutCommandeEnum::BROUILLON)
                ->setDateDemande(new \DateTime());
        }
        $data->completerMontants();

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }

    /** @return list<string> */
    private function clesEnvoyees(array $context): array
    {
        $requete = $context['request'] ?? $this->requests->getCurrentRequest();
        if (!$requete instanceof Request || $requete->getContent() === '') {
            return [];
        }
        $corps = json_decode($requete->getContent(), true);

        return \is_array($corps) ? array_map('strval', array_keys($corps)) : [];
    }
}
