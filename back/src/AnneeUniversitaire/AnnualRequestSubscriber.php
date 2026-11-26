<?php

declare(strict_types=1);

namespace App\AnneeUniversitaire;

use ApiPlatform\Metadata\CollectionOperationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\{ControllerEvent, ResponseEvent};
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class AnnualRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(private AnneeUniversitaireContext $context, private PerimetreAnnuelRegistry $registry, private EntityManagerInterface $em, private ?\ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface $metadataFactory = null) {}
    public static function getSubscribedEvents(): array { return ['kernel.controller' => ['controller', 128], 'kernel.response' => ['response', 0]]; }

    public function controller(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/') || $request->getMethod() === 'OPTIONS') return;
        if ($request->headers->get(AnneeUniversitaireContext::MODE_HEADER) === 'toutes' && !$request->isMethodSafe()) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Le mode toutes est réservé aux consultations.');
        }
        $class = $request->attributes->get('_api_resource_class');
        if (is_string($class)) {
            $baseMode = $this->registry->mode($class);
            $annual = $baseMode === ContexteAnnuel::ANNUEL;
            if ($this->registry->requeteHorsAnnee($class, $request, $this->context->body($request))) { $annual = false; $baseMode = ContexteAnnuel::INDEPENDANT; }
            $operation = $request->attributes->get('_api_operation');
            if ($operation === null && $this->metadataFactory !== null) {
                $operation = $this->metadataFactory->create($class)->getOperation($request->attributes->get('_api_operation_name'));
                $request->attributes->set('_api_operation', $operation);
            }
            $hasIdentifier = false;
            foreach (array_keys($operation?->getUriVariables() ?? []) as $key) if ($request->attributes->has($key)) $hasIdentifier = true;
            $item = !($operation instanceof CollectionOperationInterface) && $hasIdentifier;
            if ($class === \App\Entity\Users\Etudiant::class && ($item || $request->query->getBoolean('sansScolarite'))) {
                $annual = false;
                $baseMode = ContexteAnnuel::INDEPENDANT;
            }
            $mode = !$annual ? $baseMode : ($item ? ContexteAnnuel::RESSOURCE : ContexteAnnuel::ANNUEL);
            if (!$annual && $baseMode === ContexteAnnuel::ANNUEL) $mode = ContexteAnnuel::INDEPENDANT;
            $request->attributes->set('_politique_annuelle', $mode);
            if ($annual && !$item && !$this->context->toutesAutorisees($class)) $this->normaliser($request);
            if ($mode === ContexteAnnuel::RESSOURCE) {
                $entity = $this->registry->entiteDeContexte($class, $request);
                if ($entity !== null && count($years = $this->context->years($entity)) === 1) $request->attributes->set('_annee_universitaire_id', $years[0]);
            }
            return;
        }
        $controller = $event->getController();
        $method = is_array($controller) ? new \ReflectionMethod($controller[0], $controller[1]) : (is_object($controller) ? new \ReflectionMethod($controller, '__invoke') : null);
        if ($method === null) return;
        $attributes = $method->getAttributes(ContexteAnnuel::class) ?: $method->getDeclaringClass()->getAttributes(ContexteAnnuel::class);
        if ($attributes === []) {
            // Les contrôleurs techniques des bundles tiers ne sont pas des opérations métier du projet.
            if (!$this->registry->politiqueObligatoire($method->getDeclaringClass()->getName())) return;
            throw new \LogicException('Déclarer #[ContexteAnnuel(...)] sur '.$method->getDeclaringClass()->getName().'::'.$method->getName().'.');
        }
        $policy = $attributes[0]->newInstance();
        $request->attributes->set('_politique_annuelle', $policy->mode);
        if ($policy->mode === ContexteAnnuel::ANNUEL) $this->normaliser($request);
        elseif ($policy->mode === ContexteAnnuel::RESSOURCE) {
            if ($policy->ressource !== null) {
                $id = $request->attributes->get($policy->cle) ?? $request->query->get($policy->cle) ?? ($this->context->body($request)[$policy->cle] ?? null);
                if ($id !== null) {
                    $object = $this->em->getRepository($policy->ressource)->findOneBy([$policy->identifiant => $id]);
                    if ($object !== null) {
                        $years = $this->context->years($object);
                        $explicit = $this->context->explicit($request);
                        if ($explicit !== null && !in_array($explicit, $years, true)) throw new ConflictHttpException('La ressource et l’année explicite sont incompatibles.');
                        if (count($years) === 1) $request->attributes->set('_annee_universitaire_id', $years[0]);
                    }
                }
            } else {
                $year = $this->context->fromReferences($request);
                if ($year !== null) {
                    $explicit = $this->context->explicit($request);
                    if ($request->headers->get(AnneeUniversitaireContext::MODE_HEADER) === 'explicite') $explicit ??= $this->context->id($request->headers->get(AnneeUniversitaireContext::HEADER));
                    if ($explicit !== null && $explicit !== $year) throw new ConflictHttpException('La ressource et l’année explicite sont incompatibles.');
                    $request->attributes->set('_annee_universitaire_id', $year);
                }
            }
        }
    }

    private function normaliser(\Symfony\Component\HttpFoundation\Request $request): void
    {
        $id = $this->context->requireId($request);
        $request->query->set('anneeUniversitaire', $id);
        $filters = $request->attributes->get('_api_filters', []);
        $filters['anneeUniversitaire'] = $id;
        $request->attributes->set('_api_filters', $filters);
    }

    public function response(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('_politique_annuelle')) return;
        $response = $event->getResponse();
        $response->headers->set(AnneeUniversitaireContext::MODE_HEADER, $request->attributes->get('_politique_annuelle'));
        $response->setVary([AnneeUniversitaireContext::HEADER, AnneeUniversitaireContext::MODE_HEADER], false);
        if ($request->attributes->has('_annee_universitaire_id')) $response->headers->set(AnneeUniversitaireContext::HEADER, (string) $request->attributes->get('_annee_universitaire_id'));
    }
}
