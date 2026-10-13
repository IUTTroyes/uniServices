<?php

namespace IntranetBundle\Controller\PlanCours;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\PlanCours\PlanCours;
use IntranetBundle\Entity\PlanCours\PlanCoursSequence;
use IntranetBundle\Entity\Previsionnel\Previsionnel;
use IntranetBundle\Enum\PlanCours\PlanCoursEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class PlanCoursApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/plan_cours_administration', name: 'api_plan_cours_administration', methods: ['GET'])]
    #[Route('/administration/plan_cours', methods: ['GET'])]
    public function administration(Request $request): JsonResponse
    {
        $diplomeId = $request->query->get('diplome');
        $anneeUniversitaireId = $request->query->get('anneeUniversitaire');
        $semestreId = $request->query->get('semestre');
        $etatFilter = $request->query->get('etat');

        if (!$diplomeId) {
            return new JsonResponse(['message' => 'Le paramètre diplome est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $diplome = $this->entityManager->getRepository(StructureDiplome::class)->find($diplomeId);
        if (!$diplome) {
            return new JsonResponse(['message' => 'Diplôme non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $anneeUniversitaire = null;
        if ($anneeUniversitaireId) {
            $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->find($anneeUniversitaireId);
        } else {
            $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['actif' => true]);
        }

        $semestres = $diplome->getSemestres();
        $enseignementsData = [];
        $stats = [
            'total' => 0,
            'complet' => 0,
            'enCours' => 0,
            'none' => 0,
        ];

        foreach ($semestres as $semestre) {
            if ($semestreId && (int) $semestre->getId() !== (int) $semestreId) {
                continue;
            }

            foreach ($semestre->getUes() as $ue) {
                foreach ($ue->getEnseignementUes() as $enseignementUe) {
                    $enseignement = $enseignementUe->getEnseignement();
                    if (!$enseignement || isset($enseignementsData[$enseignement->getId()])) {
                        continue;
                    }

                    // Recherche du plan de cours pour cet enseignement et année
                    $planCours = null;
                    if ($anneeUniversitaire) {
                        $planCours = $this->entityManager->getRepository(PlanCours::class)->findOneBy([
                            'enseignement' => $enseignement,
                            'anneeUniversitaire' => $anneeUniversitaire,
                        ]);
                    }

                    // Enseignant référent depuis Previsionnel
                    $referent = null;
                    if ($anneeUniversitaire) {
                        $previReferent = $this->entityManager->getRepository(Previsionnel::class)->findOneBy([
                            'enseignement' => $enseignement,
                            'anneeUniversitaire' => $anneeUniversitaire,
                            'referent' => true,
                        ]);
                        if ($previReferent && $previReferent->getPersonnel()) {
                            $referent = [
                                'id' => $previReferent->getPersonnel()->getId(),
                                'nom' => $previReferent->getPersonnel()->getNom(),
                                'prenom' => $previReferent->getPersonnel()->getPrenom(),
                                'display' => $previReferent->getPersonnel()->getPrenom() . ' ' . $previReferent->getPersonnel()->getNom(),
                            ];
                        }
                    }

                    $etat = $planCours ? $planCours->getEtatPlanCours()->value : PlanCoursEnum::NONE->value;
                    $badge = $planCours ? $planCours->getEtatPlanCours()->getBadge() : PlanCoursEnum::NONE->getBadge();
                    $libelleEtat = $planCours ? $planCours->getEtatPlanCours()->getLibelle() : PlanCoursEnum::NONE->getLibelle();

                    if ($etatFilter && $etat !== $etatFilter) {
                        continue;
                    }

                    $stats['total']++;
                    if ($etat === PlanCoursEnum::COMPLET->value) {
                        $stats['complet']++;
                    } elseif ($etat === PlanCoursEnum::EN_COURS->value) {
                        $stats['enCours']++;
                    } else {
                        $stats['none']++;
                    }

                    $enseignementsData[$enseignement->getId()] = [
                        'enseignement' => [
                            'id' => $enseignement->getId(),
                            'codeEnseignement' => $enseignement->getCodeEnseignement(),
                            'libelle' => $enseignement->getLibelle(),
                            'type' => $enseignement->getType()->value,
                            'typeLibelle' => $enseignement->getType()->getLibelle(),
                            'typeBadge' => $enseignement->getType()->getBadge(),
                        ],
                        'semestre' => [
                            'id' => $semestre->getId(),
                            'libelle' => $semestre->getLibelle(),
                        ],
                        'referent' => $referent,
                        'planCours' => $planCours ? [
                            'id' => $planCours->getId(),
                            'etatPlanCours' => $etat,
                            'etatLibelle' => $libelleEtat,
                            'etatBadge' => $badge,
                            'responsable' => $planCours->getResponsable() ? [
                                'id' => $planCours->getResponsable()->getId(),
                                'display' => $planCours->getResponsable()->getPrenom() . ' ' . $planCours->getResponsable()->getNom(),
                            ] : null,
                            'cmPrevu' => $planCours->getCmPrevu(),
                            'tdPrevu' => $planCours->getTdPrevu(),
                            'tpPrevu' => $planCours->getTpPrevu(),
                            'cmRealise' => $planCours->getCmRealise(),
                            'tdRealise' => $planCours->getTdRealise(),
                            'tpRealise' => $planCours->getTpRealise(),
                            'nbSequences' => $planCours->getPlanCoursSequences()->count(),
                        ] : [
                            'id' => null,
                            'etatPlanCours' => PlanCoursEnum::NONE->value,
                            'etatLibelle' => PlanCoursEnum::NONE->getLibelle(),
                            'etatBadge' => PlanCoursEnum::NONE->getBadge(),
                            'responsable' => null,
                            'nbSequences' => 0,
                        ],
                    ];
                }
            }
        }

        return new JsonResponse([
            'diplome' => [
                'id' => $diplome->getId(),
                'libelle' => $diplome->getLibelle(),
            ],
            'anneeUniversitaire' => $anneeUniversitaire ? [
                'id' => $anneeUniversitaire->getId(),
                'libelle' => $anneeUniversitaire->getLibelle(),
            ] : null,
            'stats' => $stats,
            'enseignements' => array_values($enseignementsData),
        ]);
    }

    #[Route('/plan_cours_personnel', name: 'api_plan_cours_personnel', methods: ['GET'])]
    #[Route('/personnel/plan_cours', methods: ['GET'])]
    public function personnel(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $personnelId = $request->query->get('personnel') ?: ($user instanceof Personnel ? $user->getId() : null);
        $anneeUniversitaireId = $request->query->get('anneeUniversitaire');

        if (!$personnelId) {
            return new JsonResponse(['message' => 'Personnel non spécifié.'], Response::HTTP_BAD_REQUEST);
        }

        $personnel = $this->entityManager->getRepository(Personnel::class)->find($personnelId);
        if (!$personnel) {
            return new JsonResponse(['message' => 'Personnel non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $anneeUniversitaire = null;
        if ($anneeUniversitaireId) {
            $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->find($anneeUniversitaireId);
        } else {
            $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['actif' => true]);
        }

        if (!$anneeUniversitaire) {
            return new JsonResponse(['message' => 'Année universitaire non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer tous les prévisionnels de l'enseignant pour cette année
        $previsionnels = $this->entityManager->getRepository(Previsionnel::class)->findBy([
            'personnel' => $personnel,
            'anneeUniversitaire' => $anneeUniversitaire,
        ]);

        $enseignementsMap = [];

        foreach ($previsionnels as $previsionnel) {
            $ens = $previsionnel->getEnseignement();
            if (!$ens) {
                continue;
            }

            if (!isset($enseignementsMap[$ens->getId()])) {
                // Trouver le plan de cours existant
                $planCours = $this->entityManager->getRepository(PlanCours::class)->findOneBy([
                    'enseignement' => $ens,
                    'anneeUniversitaire' => $anneeUniversitaire,
                ]);

                // Trouver le semestre via enseignementUes
                $semestre = null;
                $ue = $ens->getEnseignementUes()->first();
                if ($ue && $ue->getUe()) {
                    $semestre = $ue->getUe()->getSemestre();
                }

                $etat = $planCours ? $planCours->getEtatPlanCours()->value : PlanCoursEnum::NONE->value;
                $badge = $planCours ? $planCours->getEtatPlanCours()->getBadge() : PlanCoursEnum::NONE->getBadge();
                $libelleEtat = $planCours ? $planCours->getEtatPlanCours()->getLibelle() : PlanCoursEnum::NONE->getLibelle();

                $enseignementsMap[$ens->getId()] = [
                    'enseignement' => [
                        'id' => $ens->getId(),
                        'codeEnseignement' => $ens->getCodeEnseignement(),
                        'libelle' => $ens->getLibelle(),
                        'type' => $ens->getType()->value,
                        'typeLibelle' => $ens->getType()->getLibelle(),
                        'typeBadge' => $ens->getType()->getBadge(),
                        'cmHeures' => $ens->getHeures()['cm'] ?? 0,
                        'tdHeures' => $ens->getHeures()['td'] ?? 0,
                        'tpHeures' => $ens->getHeures()['tp'] ?? 0,
                    ],
                    'semestre' => $semestre ? [
                        'id' => $semestre->getId(),
                        'libelle' => $semestre->getLibelle(),
                        'diplome' => $semestre->getDiplome() ? [
                            'id' => $semestre->getDiplome()->getId(),
                            'libelle' => $semestre->getDiplome()->getLibelle(),
                        ] : null,
                    ] : null,
                    'isReferent' => (bool) $previsionnel->isReferent(),
                    'previsionnelId' => $previsionnel->getId(),
                    'planCours' => $planCours ? [
                        'id' => $planCours->getId(),
                        'etatPlanCours' => $etat,
                        'etatLibelle' => $libelleEtat,
                        'etatBadge' => $badge,
                        'responsable' => $planCours->getResponsable() ? [
                            'id' => $planCours->getResponsable()->getId(),
                            'display' => $planCours->getResponsable()->getPrenom() . ' ' . $planCours->getResponsable()->getNom(),
                        ] : null,
                        'cmPrevu' => $planCours->getCmPrevu(),
                        'tdPrevu' => $planCours->getTdPrevu(),
                        'tpPrevu' => $planCours->getTpPrevu(),
                        'projetPrevu' => $planCours->getProjetPrevu(),
                        'cmRealise' => $planCours->getCmRealise(),
                        'tdRealise' => $planCours->getTdRealise(),
                        'tpRealise' => $planCours->getTpRealise(),
                        'projetRealise' => $planCours->getProjetRealise(),
                        'nbSequences' => $planCours->getPlanCoursSequences()->count(),
                        'updatedAt' => $planCours->getUpdatedAt()?->format('d/m/Y H:i'),
                    ] : null,
                ];
            } elseif ($previsionnel->isReferent()) {
                $enseignementsMap[$ens->getId()]['isReferent'] = true;
            }
        }

        return new JsonResponse([
            'anneeUniversitaire' => [
                'id' => $anneeUniversitaire->getId(),
                'libelle' => $anneeUniversitaire->getLibelle(),
            ],
            'personnel' => [
                'id' => $personnel->getId(),
                'display' => $personnel->getPrenom() . ' ' . $personnel->getNom(),
            ],
            'modules' => array_values($enseignementsMap),
        ]);
    }

    #[Route('/plan_cours/{id}/recopie', name: 'api_plan_cours_recopie', methods: ['POST'])]
    public function recopie(int $id, Request $request): JsonResponse
    {
        $planCours = $this->entityManager->getRepository(PlanCours::class)->find($id);
        if (!$planCours) {
            return new JsonResponse(['message' => 'Plan de cours cible non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $sourceId = $payload['sourcePlanCoursId'] ?? null;

        if (!$sourceId) {
            return new JsonResponse(['message' => 'sourcePlanCoursId requis.'], Response::HTTP_BAD_REQUEST);
        }

        $source = $this->entityManager->getRepository(PlanCours::class)->find($sourceId);
        if (!$source) {
            return new JsonResponse(['message' => 'Plan de cours source non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Recopie des informations pédagogiques
        $planCours->setCmPrevu($source->getCmPrevu());
        $planCours->setTdPrevu($source->getTdPrevu());
        $planCours->setTpPrevu($source->getTpPrevu());
        $planCours->setProjetPrevu($source->getProjetPrevu());
        $planCours->setNbNotes($source->getNbNotes());
        $planCours->setBibliographie($source->getBibliographie());
        $planCours->setDescription($source->getDescription());
        $planCours->setObjectifs($source->getObjectifs());
        $planCours->setModalitesEvaluations($source->getModalitesEvaluations());
        $planCours->setLivrables($source->getLivrables());
        $planCours->setMiseEnOeuvre($source->getMiseEnOeuvre());
        $planCours->setModaliteEncadrementAutonomie($source->getModaliteEncadrementAutonomie());
        $planCours->setSaisiePlanCours($source->getSaisiePlanCours());
        $planCours->setCommentairesStep1($source->getCommentairesStep1());
        $planCours->setCommentairesStep2($source->getCommentairesStep2());

        // Nettoyage et recopie des séquences
        foreach ($planCours->getPlanCoursSequences() as $existingSequence) {
            $this->entityManager->remove($existingSequence);
        }
        $planCours->getPlanCoursSequences()->clear();

        foreach ($source->getPlanCoursSequences() as $sourceSeq) {
            $newSeq = new PlanCoursSequence();
            $newSeq->setPlanCours($planCours);
            $newSeq->setOrdre($sourceSeq->getOrdre());
            $newSeq->setProgramme($sourceSeq->getProgramme());
            $newSeq->setNbHeures($sourceSeq->getNbHeures());
            $newSeq->setCommentaire($sourceSeq->getCommentaire());
            $this->entityManager->persist($newSeq);
            $planCours->addPlanCoursSequence($newSeq);
        }

        $planCours->setEtatPlanCours(PlanCoursEnum::EN_COURS);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Plan de cours recopié avec succès.',
            'id' => $planCours->getId(),
        ]);
    }

    #[Route('/plan_cours/init_module', name: 'api_plan_cours_init_module', methods: ['POST'])]
    public function initModule(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?: [];
        $enseignementId = $payload['enseignementId'] ?? null;
        $anneeUniversitaireId = $payload['anneeUniversitaireId'] ?? null;

        if (!$enseignementId || !$anneeUniversitaireId) {
            return new JsonResponse(['message' => 'enseignementId et anneeUniversitaireId sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $enseignement = $this->entityManager->getRepository(ScolEnseignement::class)->find($enseignementId);
        $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->find($anneeUniversitaireId);

        if (!$enseignement || !$anneeUniversitaire) {
            return new JsonResponse(['message' => 'Enseignement ou année universitaire non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier s'il existe déjà
        $planCours = $this->entityManager->getRepository(PlanCours::class)->findOneBy([
            'enseignement' => $enseignement,
            'anneeUniversitaire' => $anneeUniversitaire,
        ]);

        if (!$planCours) {
            $planCours = new PlanCours();
            $planCours->setEnseignement($enseignement);
            $planCours->setAnneeUniversitaire($anneeUniversitaire);

            // Utilisateur connecté comme responsable par défaut
            $user = $this->getUser();
            if ($user instanceof Personnel) {
                $planCours->setResponsable($user);
            }

            // Heures prévues par défaut depuis l'enseignement PN
            $heures = $enseignement->getHeures();
            $planCours->setCmPrevu(isset($heures['cm']) ? (float) $heures['cm'] : null);
            $planCours->setTdPrevu(isset($heures['td']) ? (float) $heures['td'] : null);
            $planCours->setTpPrevu(isset($heures['tp']) ? (float) $heures['tp'] : null);
            $planCours->setProjetPrevu(isset($heures['projet']) ? (float) $heures['projet'] : null);
            $planCours->setNbNotes($enseignement->getNbNotes() ?: 2);
            $planCours->setDescription($enseignement->getDescription());
            $planCours->setObjectifs($enseignement->getObjectif());
            $planCours->setLivrables($enseignement->getLivrables());
            $planCours->setEtatPlanCours(PlanCoursEnum::EN_COURS);

            $this->entityManager->persist($planCours);
            $this->entityManager->flush();
        }

        return new JsonResponse([
            'id' => $planCours->getId(),
            'message' => 'Plan de cours initialisé.',
        ]);
    }
}
