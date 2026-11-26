<?php

namespace App\State\Processor\Structure;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureService;
use App\Extension\SuppressionBloqueeInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Suppression d'un service de l'établissement : refusée (409) tant qu'il est utilisé. La suppression emporterait
 * sinon les éléments que les bundles lui rattachent et le rattachement de ses personnels ; une référence ailleurs
 * (demande d'achat…) est signalée par la base.
 *
 * @implements ProcessorInterface<StructureService, void>
 */
final class StructureServiceDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $remove,
        /** @var iterable<SuppressionBloqueeInterface> */
        #[AutowireIterator('app.suppression_bloquee')]
        private readonly iterable $blocages = [],
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof StructureService && $this->estUtilise($data)) {
            throw new ConflictHttpException('Ce service a des personnels ou des catégories helpdesk rattachés : il ne peut pas être supprimé.');
        }
        try {
            return $this->remove->process($data, $operation, $uriVariables, $context);
        } catch (ForeignKeyConstraintViolationException) {
            throw new ConflictHttpException('Ce service est utilisé (demandes d\'achat…) : il ne peut pas être supprimé.');
        }
    }

    private function estUtilise(StructureService $service): bool
    {
        if (!$service->getPersonnel()->isEmpty()) {
            return true;
        }
        foreach ($this->blocages as $blocage) {
            if ($blocage->bloque($service)) {
                return true;
            }
        }

        return false;
    }
}
