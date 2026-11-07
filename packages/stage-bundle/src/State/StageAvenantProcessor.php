<?php

namespace StageBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Security\StageAccess;
use StageBundle\Service\Notification\StageNotifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Demande d'avenant : seul le service des stages peut créer un avenant déjà validé (la validation passe par
 * POST /api/stage_avenants/{id}/validate). Une demande d'étudiant prévient les responsables de la période.
 *
 * @implements ProcessorInterface<mixed, mixed>
 */
final class StageAvenantProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        private readonly Security $security,
        private readonly StageAccess $access,
        private readonly StageNotifier $notifier,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof StageAvenant || $data->getStageEtudiant() === null) {
            return $this->persist->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        $auteur = $user instanceof Etudiant || $user instanceof Personnel ? $user : null;
        $gestion = $this->access->peutGerer($auteur, $data->getStageEtudiant());
        if (!$gestion) {
            $data->setValide(false);
            $data->setDateValidation(null);
        }

        $resultat = $this->persist->process($data, $operation, $uriVariables, $context);

        if (!$gestion) {
            $this->notifier->avenantDemande($data, $auteur);
        }

        return $resultat;
    }
}
