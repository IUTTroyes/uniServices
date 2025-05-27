<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\FicheHeure;
use App\Entity\Users\Personnel;
use App\Enum\FicheHeureStatutEnum;
use Symfony\Component\Security\Core\Security;
use DateTimeImmutable;
// Optional for later refinement:
// use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class FicheHeureRejectProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {
    }

    /**
     * @param mixed $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return FicheHeure
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FicheHeure
    {
        if (!$data instanceof FicheHeure) {
            return $data;
        }

        $user = $this->security->getUser();
        if (!$user instanceof Personnel) {
            return $data;
        }

        if ($data->getStatut() !== FicheHeureStatutEnum::SOUMISE) {
            return $data;
        }

        if (empty($data->getCommentaireValidation())) {
            // Optional: throw new BadRequestHttpException('Un commentaire de validation est requis pour le rejet.');
            return $data;
        }

        // This processor is specifically for a PATCH operation changing the status to REJETEE.
        if ($operation->getMethod() === 'PATCH') {
            $data->setStatut(FicheHeureStatutEnum::REJETEE);
            $data->setDateValidation(new DateTimeImmutable());
            $data->setValidateur($user);

            // Delegate to the standard persist processor to save the entity
            $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }
        // If conditions are not met, the original data is returned without changes by this processor.

        return $data;
    }
}
