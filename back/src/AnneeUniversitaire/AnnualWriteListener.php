<?php

declare(strict_types=1);

namespace App\AnneeUniversitaire;

use App\Entity\Structure\StructureAnneeUniversitaire;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\{ConflictHttpException, UnprocessableEntityHttpException};

/** Cohérence des écritures HTTP ; les commandes passent explicitement leur année au métier. */
#[AsDoctrineListener(event: Events::onFlush, priority: 128)]
final readonly class AnnualWriteListener
{
    public function __construct(private AnneeUniversitaireContext $context, private PerimetreAnnuelRegistry $registry) {}
    public function onFlush(OnFlushEventArgs $event): void
    {
        $request = $this->context->request();
        if ($request === null || !$request->attributes->has('_politique_annuelle')) return;
        $policy = $request->attributes->get('_politique_annuelle');
        if (in_array($policy, [ContexteAnnuel::INDEPENDANT, ContexteAnnuel::TRANSVERSAL], true)) return;
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();
        $entities = [...$uow->getScheduledEntityInsertions(), ...$uow->getScheduledEntityUpdates(), ...$uow->getScheduledEntityDeletions()];
        foreach ([...$uow->getScheduledCollectionUpdates(), ...$uow->getScheduledCollectionDeletions()] as $collection) $entities[] = $collection->getOwner();
        foreach ($entities as $entity) {
            if ($entity instanceof \App\Entity\Users\Etudiant) continue; // L’identité est indépendante ; ses scolarités sont contrôlées séparément.
            $class = $em->getClassMetadata($entity::class)->getName();
            $path = $this->registry->path($class);
            if ($path === null) continue;
            if ($this->context->isModele($entity)) continue;
            $years = $this->context->years($entity);
            if (!$request->attributes->has('_annee_universitaire_id') && $policy === ContexteAnnuel::RESSOURCE && count($years) === 1) {
                $explicit = $this->context->explicit($request);
                if ($request->headers->get(AnneeUniversitaireContext::MODE_HEADER) === 'explicite') $explicit ??= $this->context->id($request->headers->get(AnneeUniversitaireContext::HEADER));
                if ($explicit !== null && $explicit !== $years[0]) throw new ConflictHttpException('L’année explicite contredit la ressource modifiée.');
                $request->attributes->set('_annee_universitaire_id', $years[0]);
            }
            $year = $this->context->requireId($request);
            if ($years === [] && !str_contains($path, '.')) {
                $em->getClassMetadata($class)->setFieldValue($entity, $path, $em->getReference(StructureAnneeUniversitaire::class, $year));
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($class), $entity);
                $years = [$year];
            }
            if ($years === []) throw new UnprocessableEntityHttpException('La ressource annuelle doit être rattachée à une année universitaire.');
            if (!in_array($year, $years, true)) throw new ConflictHttpException('Écriture refusée : ressource hors du contexte annuel de la requête.');
            foreach ($uow->getEntityChangeSet($entity) as $change) {
                if (!is_object($change[0])) continue;
                $oldClass = $change[0]::class;
                if ($em->getMetadataFactory()->isTransient($oldClass)) continue;
                $oldYears = $this->context->years($change[0]);
                if ($oldYears !== [] && !in_array($year, $oldYears, true)) throw new ConflictHttpException('Le changement de rattachement traverse plusieurs années universitaires.');
            }
        }
    }
}
