<?php

declare(strict_types=1);

namespace IntranetBundle\EventListener;

use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\{StructureAnnee, StructureAnneeUniversitaire, StructurePn, StructureDiplome, StructureTypeDiplome, StructureSemestre, StructureUe, StructureGroupe};
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Commission\Service\CommissionWriteContext;
use IntranetBundle\Entity\Commission\{SousCommission, SousCommissionRevision, ResultatSousCommission};
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** Couvre les écritures API, contrôleurs, commandes et les collections Doctrine. */
#[AsDoctrineListener(event: Events::onFlush)]
final readonly class ScolariteIntegrityListener
{
    public function __construct(private Security $security, private CommissionWriteContext $context) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();
        $entities = [...$uow->getScheduledEntityInsertions(), ...$uow->getScheduledEntityUpdates(), ...$uow->getScheduledEntityDeletions()];
        foreach ([...$uow->getScheduledCollectionUpdates(), ...$uow->getScheduledCollectionDeletions()] as $collection) $entities[] = $collection->getOwner();
        $semestres = [];
        foreach ($entities as $entity) {
            $changes = $uow->getEntityChangeSet($entity);
            $old = $uow->getOriginalEntityData($entity);
            if ($entity instanceof SousCommission || $entity instanceof SousCommissionRevision || $entity instanceof ResultatSousCommission) {
                if (!$this->context->isActive()) throw new ConflictHttpException('Les résultats arrêtés ne peuvent être modifiés que par la validation ou la réouverture.');
                continue;
            }
            if ($entity instanceof EtudiantNote && !in_array($entity, $uow->getScheduledEntityDeletions(), true)) {
                $this->checkNote($entity);
                $this->auditNote($entity, $old, $changes, $em);
            }
            if ($entity instanceof EtudiantScolariteSemestre || $entity instanceof EtudiantScolarite) {
                $inscriptions = $entity instanceof EtudiantScolarite ? $entity->getScolariteSemestre()->toArray() : [$entity];
                foreach ($inscriptions as $inscription) {
                    foreach ($inscription->getNote() as $note) {
                        if (!in_array($note, $uow->getScheduledEntityDeletions(), true)) $this->checkNote($note);
                    }
                }
            }
            if ($entity instanceof EtudiantScolariteSemestre && !$this->context->isActive()) {
                if (array_intersect(array_keys($changes), ['moyenne', 'rang', 'decision'])) {
                    throw new ConflictHttpException('Les résultats semestriels sont écrits uniquement lors de la validation.');
                }
            }
            if ($this->context->isActive()) continue;
            foreach ($this->semestres($entity, $em) as $semestre) $semestres[spl_object_id($semestre)] = $semestre;
            // Un changement de rattachement doit également contrôler l’ancien périmètre.
            foreach (['semestre', 'annee', 'pn', 'diplome', 'scolariteSemestre', 'ue', 'enseignement', 'scolarite', 'typeDiplome', 'anneeUniversitaire'] as $property) {
                if (isset($changes[$property]) && is_object($changes[$property][0])) {
                    foreach ($this->semestres($changes[$property][0], $em) as $semestre) $semestres[spl_object_id($semestre)] = $semestre;
                }
            }
        }
        foreach ($semestres as $semestre) {
            if ($semestre->getId() !== null) {
                $validee = $em->getConnection()->fetchOne('SELECT validee FROM intranet_sous_commission WHERE semestre_id = ?', [$semestre->getId()]);
                if ($validee) throw new ConflictHttpException('Résultats validés : rouvrez explicitement la sous-commission avant toute modification.');
            }
            if (in_array($semestre, $uow->getScheduledEntityDeletions(), true)) continue;
            $semestre->touchResultats();
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(StructureSemestre::class), $semestre);
        }
    }

    private function checkNote(EtudiantNote $note): void
    {
        $evaluation = $note->getEvaluation();
        $inscription = $note->getScolariteSemestre();
        $semestre = $inscription?->getSemestre();
        if ($evaluation === null || $semestre === null || $evaluation->getSemestre() !== $semestre
            || $evaluation->getAnneeUniversitaire() !== $inscription->getScolarite()?->getAnneeUniversitaire()
            || $evaluation->getAnneeUniversitaire() !== $semestre->getAnnee()?->getPn()?->getAnneeUniversitaire()) {
            throw new UnprocessableEntityHttpException('Évaluation et inscription doivent appartenir au même semestre et à la même année universitaire.');
        }
    }

    private function auditNote(EtudiantNote $note, array $old, array $changes, EntityManagerInterface $em): void
    {
        // Un historique envoyé par un client ne peut jamais remplacer celui enregistré.
        if (isset($changes['historique']) && $old !== [] && ($changes['historique'][1] !== $old['historique'])) {
            throw new UnprocessableEntityHttpException('L’historique des notes est géré exclusivement par le serveur.');
        }
        if ($old === [] || !array_intersect(array_keys($changes), ['note', 'presenceStatut', 'evaluation', 'scolariteSemestre'])) return;
        $historique = $old['historique'] ?? [];
        $historique[] = ['note' => $old['note'] ?? null, 'presenceStatut' => $old['presenceStatut'] ?? null,
            'evaluationId' => ($old['evaluation'] ?? null)?->getId(), 'inscriptionId' => ($old['scolariteSemestre'] ?? null)?->getId(),
            'date' => (new \DateTimeImmutable())->format(DATE_ATOM), 'auteurId' => $this->security->getUser()?->getId(),
            'motif' => $note->getMotifModification() ?: 'Saisie ou correction du résultat'];
        $note->setHistorique($historique);
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($em->getClassMetadata(EtudiantNote::class), $note);
    }

    /** @return StructureSemestre[] */
    private function semestres(object $entity, EntityManagerInterface $em): array
    {
        if ($entity instanceof StructureSemestre) return [$entity];
        if ($entity instanceof EtudiantNote) return array_values(array_filter([$entity->getEvaluation()?->getSemestre(), $entity->getScolariteSemestre()?->getSemestre()]));
        if ($entity instanceof ScolEvaluation || $entity instanceof EtudiantScolariteSemestre || $entity instanceof StructureUe) return array_values(array_filter([$entity->getSemestre()]));
        if ($entity instanceof ScolEnseignementUe) return array_values(array_filter([$entity->getUe()?->getSemestre()]));
        if ($entity instanceof StructureGroupe) return $entity->getSemestres()->toArray();
        if ($entity instanceof StructureAnnee) return $entity->getSemestres()->toArray();
        if ($entity instanceof EtudiantScolarite) return array_values(array_filter(array_map(static fn ($i) => $i->getSemestre(), $entity->getScolariteSemestre()->toArray())));
        if ($entity instanceof ScolEnseignement) return array_values(array_filter(array_map(static fn ($l) => $l->getUe()?->getSemestre(), $entity->getEnseignementUes()->toArray())));
        if ($entity instanceof StructurePn) {
            $out = [];
            foreach ($entity->getAnnees() as $annee) array_push($out, ...$annee->getSemestres()->toArray());
            return $out;
        }
        if ($entity instanceof StructureDiplome || $entity instanceof StructureTypeDiplome || $entity instanceof StructureAnneeUniversitaire) {
            if ($entity->getId() === null) return [];
            $qb = $em->getRepository(StructureSemestre::class)->createQueryBuilder('s')->join('s.annee', 'a')->join('a.pn', 'pn');
            if ($entity instanceof StructureDiplome) $qb->where('pn.diplome = :owner');
            elseif ($entity instanceof StructureAnneeUniversitaire) $qb->where('pn.anneeUniversitaire = :owner');
            else $qb->join('pn.diplome', 'd')->where('d.typeDiplome = :owner');
            return $qb->setParameter('owner', $entity)->getQuery()->getResult();
        }
        return [];
    }
}
