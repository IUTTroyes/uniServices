<?php

namespace App\State\Processor\Evaluation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureGroupe;
use App\Enum\EtatEvaluationEnum;
use App\Enum\TypeGroupeEnum;
use App\Repository\EtudiantNoteRepository;
use App\Repository\Structure\StructureGroupeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class ScolEvaluationInitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StructureGroupeRepository $groupeRepository,
        private readonly EtudiantNoteRepository $noteRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof ScolEvaluation) {
            return $data;
        }

        // Persist changes made to the evaluation first (Doctrine will track the entity)
        $this->em->flush();

        $etatActuel = $data->getEtat();
        if ($etatActuel?->isEtatManuel()) {
            return $data;
        }

        // Conditions d'initialisation remplies ? (pour marquer l'évaluation comme initialisée)
        $coeffOk = null !== $data->getCoeff();
        $typeGroupe = $data->getTypeGroupe();
        $typeOk = $typeGroupe instanceof TypeGroupeEnum;
        $persoOk = !$data->getPersonnelAutorise()->isEmpty();
        $dateOk = null !== $data->getDate();
        $semestre = $data->getSemestre();

        // Si les champs de base ne sont pas remplis, l'évaluation reste non initialisée
        if (!($coeffOk && $typeOk && $persoOk)) {
            $data->setEtat(EtatEvaluationEnum::ETAT_NON_INITIALISEE);
            $this->em->flush();
            return $data; // ne rien faire tant que les champs requis ne sont pas remplis
        }

        // Évaluation initialisée avec ou sans date
        if (!$dateOk) {
            $data->setEtat(EtatEvaluationEnum::ETAT_INITIALISEE);
            $this->em->flush();
            return $data;
        }

        // Date fournie : l'évaluation est planifiée jusqu'à saisie complète
        $data->setEtat(EtatEvaluationEnum::ETAT_PLANIFIEE);
        $this->em->flush();

        // Si pas de semestre défini, on s'arrête après la mise à jour de l'état
        if (null === $semestre) {
            return $data;
        }

        // Récupération des groupes du semestre pour le type choisi
        $groupes = $this->groupeRepository->createQueryBuilder('g')
            ->join('g.semestres', 's')
            ->andWhere('s.id = :semestreId')
            ->andWhere('g.type = :type')
            ->setParameter('semestreId', $semestre->getId())
            ->setParameter('type', $typeGroupe)
            ->getQuery()
            ->getResult();

        // Construire l'ensemble des scolarités semestre cibles (unique)
        $targets = [];
        /** @var StructureGroupe $groupe */
        foreach ($groupes as $groupe) {
            /** @var EtudiantScolariteSemestre $ess */
            foreach ($groupe->getScolariteSemestres() as $ess) {
                if (null === $ess->getId()) {
                    continue;
                }
                $targets[$ess->getId()] = $ess;
            }
        }

        $expected = count($targets);
        $existingCount = $this->noteRepository->countByEvaluation($data);

        if ($expected === 0 && $existingCount === 0) {
            throw new BadRequestHttpException('Aucun étudiant trouvé pour le semestre et le type de groupe sélectionnés. Initialisation impossible.');
        }

        // Créer les notes manquantes
        $created = 0;
        foreach ($targets as $ess) {
            $existing = $this->noteRepository->findOneBy([
                'evaluation' => $data,
                'scolariteSemestre' => $ess,
            ]);
            if ($existing) {
                continue;
            }

            $note = new EtudiantNote();
            $note->setEvaluation($data);
            $note->setScolariteSemestre($ess);
            $note->setScolarite($ess->getScolarite());
            // Initialisation: note=null, presenceStatut=present, commentaire=null
            $note->setPresenceStatut(EtudiantNote::STATUT_PRESENT);
            $note->setCommentaire(null);
            $note->setUuid(Uuid::v4());
            $this->em->persist($note);
            $created++;
        }

        if ($created === 0 && $existingCount === 0) {
            throw new BadRequestHttpException('La création des notes a échoué. Aucune note n\'a été générée.');
        }

        $this->em->flush();

        // Mettre à jour l'état de l'évaluation après génération/contrôle des notes
        $total = $this->noteRepository->countByEvaluation($data);
        $completed = $this->noteRepository->countCompletedByEvaluation($data);
        if ($total > 0 && $completed >= $total) {
            $etat = EtatEvaluationEnum::ETAT_COMPLETEE;
        } else {
            $today = new \DateTimeImmutable('today');
            $evaluationDate = \DateTimeImmutable::createFromInterface($data->getDate());
            $etat = $evaluationDate < $today
                ? EtatEvaluationEnum::ETAT_TERMINEE
                : EtatEvaluationEnum::ETAT_PLANIFIEE;
        }
        $data->setEtat($etat);
        $this->em->flush();

        return $data;
    }
}
