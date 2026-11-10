<?php

namespace FinanceBundle\Serializer;

use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\ActionWorkflowEnum;
use FinanceBundle\Enum\StatutCommandeEnum;
use FinanceBundle\Security\FinanceAccess;
use FinanceBundle\Workflow\FinanceWorkflow;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Ajoute au détail d'une demande (groupe finance:detail) les droits de la personne connectée, calculés par le serveur :
 * le front affiche boutons et champs d'après eux au lieu de recopier les règles.
 *
 *   "droits": { "actions": [{ "action", "libelle", "severite", "commentaire", "confirmer" }], "champs": [...],
 *               "supprimer": bool, "deposerDocument": bool, "gererReceptions": bool, "decisionDocument": bool,
 *               "bonsLivraison": bool, "viserReceptions": bool }
 */
final class FinanceBonCommandeNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const DEJA_APPELE = 'finance_bon_commande_droits';

    public function __construct(
        private readonly Security $security,
        private readonly FinanceAccess $access,
        private readonly FinanceWorkflow $workflow,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::DEJA_APPELE] = true;
        $normalise = $this->normalizer->normalize($data, $format, $context);

        if (\is_array($normalise) && $data instanceof FinanceBonCommande && \in_array('finance:detail', (array) ($context['groups'] ?? []), true)) {
            $user = $this->security->getUser();
            $normalise['droits'] = [
                'actions' => array_map(static fn (ActionWorkflowEnum $a): array => [
                    'action' => $a->value,
                    'libelle' => $a->getLibelle(),
                    'severite' => $a->getSeverite(),
                    'commentaire' => $a->exigeCommentaire(),
                    'confirmer' => $a->aConfirmer(),
                ], $this->workflow->actionsPossibles($user, $data)),
                'champs' => $this->workflow->champsModifiables($user, $data),
                'supprimer' => $this->access->peutSupprimer($user, $data),
                'deposerDocument' => $this->access->peutDeposerDocument($user, $data),
                'gererReceptions' => $this->access->estGestionnaire($user, $data),
                'decisionDocument' => $this->workflow->peutDecider($user, $data),
                // Service fait : dépôt des bons de livraison (réceptions non visées) et visa du service financier.
                'bonsLivraison' => $data->getStatut() === StatutCommandeEnum::SERVICE_FAIT_CONSTATE
                    && ($this->access->estGestionnaire($user, $data) || $this->access->estDemandeur($user, $data)
                        || FinanceAccess::memePersonne($data->getResponsable(), $user)),
                'viserReceptions' => $data->getStatut() === StatutCommandeEnum::SERVICE_FAIT_CONSTATE && $this->access->estGestionnaire($user, $data),
            ];
        }

        return $normalise;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FinanceBonCommande && !isset($context[self::DEJA_APPELE]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [FinanceBonCommande::class => false];
    }
}
