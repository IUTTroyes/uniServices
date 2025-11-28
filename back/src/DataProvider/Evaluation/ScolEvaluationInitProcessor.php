<?php

namespace App\DataProvider\Evaluation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureGroupe;
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
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Persist changes made to the evaluation first (Doctrine will track the entity)
        $this->em->flush();

        if (!$data instanceof ScolEvaluation) {
            return $data;
        }

        // Conditions d'initialisation remplies ? (pour marquer l'évaluation comme initialisée)
        $coeffOk = null !== $data->getCoeff();
        $typeGroupe = $data->getTypeGroupe();
        $typeOk = $typeGroupe instanceof TypeGroupeEnum;
        $persoOk = !$data->getPersonnelAutorise()->isEmpty();
        $dateOk = null !== $data->getDate();
        $semestre = $data->getSemestre();

        // Si tous les champs attendus sont remplis, on passe l'état à "initialisee"
        if ($coeffOk && $typeOk && $persoOk && $dateOk) {
            if ($data->getEtat() !== 'initialisee') {
                $data->setEtat('initialisee');
                $this->em->flush();
            }
        } else {
            return $data; // ne rien faire tant que les champs requis ne sont pas remplis
        }

        // Si pas de semestre défini, on s'arrête après avoir marqué l'état à initialisee
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
                if (null === $ess->getId()) { continue; }
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

        // Mettre à jour l'état de l'évaluation
        $total = $this->noteRepository->countByEvaluation($data);
        $completed = $this->noteRepository->countCompletedByEvaluation($data);
        $etat = ($total > 0 && $completed >= $total) ? 'complet' : 'planifiee';
        $data->setEtat($etat);
        $this->em->flush();

        return $data;
    }
}
