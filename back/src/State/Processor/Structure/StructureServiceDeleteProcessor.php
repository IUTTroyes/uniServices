<?php

namespace App\State\Processor\Structure;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Suppression d'un service de l'établissement : refusée (409) tant qu'il est utilisé. La suppression emporterait
 * sinon ses catégories helpdesk (orphanRemoval) et le rattachement de ses personnels ; une référence ailleurs
 * (demande d'achat…) est signalée par la base.
 *
 * @implements ProcessorInterface<StructureService, void>
 */
final class StructureServiceDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $remove,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof StructureService && (!$data->getHelpdeskCategories()->isEmpty() || !$data->getPersonnel()->isEmpty())) {
            throw new ConflictHttpException('Ce service a des personnels ou des catégories helpdesk rattachés : il ne peut pas être supprimé.');
        }
        try {
            return $this->remove->process($data, $operation, $uriVariables, $context);
        } catch (ForeignKeyConstraintViolationException) {
            throw new ConflictHttpException('Ce service est utilisé (demandes d\'achat…) : il ne peut pas être supprimé.');
        }
    }
}
