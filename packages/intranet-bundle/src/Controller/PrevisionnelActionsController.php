<?php

declare(strict_types=1);

namespace IntranetBundle\Controller;

use App\AnneeUniversitaire\ContexteAnnuel;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\Previsionnel\Previsionnel;
use IntranetBundle\Repository\Previsionnel\PrevisionnelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\HttpKernel\Exception\{ConflictHttpException, UnprocessableEntityHttpException};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/intranet/previsionnel/semestres/{id}', requirements: ['id' => '\d+'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[ContexteAnnuel(ContexteAnnuel::RESSOURCE, 'Le semestre cible fixe l’année de l’opération.', StructureSemestre::class)]
final class PrevisionnelActionsController extends AbstractController
{
    private const TYPES = ['CM', 'TD', 'TP', 'Projet'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PrevisionnelRepository $previsionnels,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/lignes', name: 'api_previsionnel_supprimer_lignes', methods: ['DELETE'])]
    public function supprimer(#[MapEntity(id: 'id')] StructureSemestre $semestre, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('CAN_EDIT_SEMESTRE', $semestre);
        $ids = $request->toArray()['ids'] ?? null;
        if (!is_array($ids) || !array_is_list($ids) || $ids === [] || array_filter($ids, static fn ($id) => !is_int($id) || $id <= 0)) {
            throw new UnprocessableEntityHttpException('La liste des lignes à supprimer est invalide.');
        }

        $supprimees = $this->em->wrapInTransaction(function () use ($semestre, $ids): int {
            $this->em->lock($semestre, LockMode::PESSIMISTIC_WRITE);
            $lignes = $this->previsionnels->findForSemester($semestre);
            $attendus = array_map(static fn (Previsionnel $ligne) => $ligne->getId(), $lignes);
            sort($ids);
            sort($attendus);
            if ($ids !== $attendus) {
                throw new ConflictHttpException('Le prévisionnel a changé. Rechargez le semestre avant de le vider.');
            }
            foreach ($lignes as $ligne) {
                $this->denyAccessUnlessGranted('CAN_DELETE_PREVISIONNEL', $ligne);
                $this->verifierRattachement($semestre, $ligne->getEnseignement());
            }
            foreach ($lignes as $ligne) {
                $this->em->remove($ligne);
            }
            return count($lignes);
        });

        return $this->json(['supprimees' => $supprimees]);
    }

    #[Route('/import', name: 'api_previsionnel_import', methods: ['POST'])]
    public function importer(#[MapEntity(id: 'id')] StructureSemestre $semestre, Request $request): JsonResponse
    {
        $lignes = $request->toArray()['lignes'] ?? null;
        if (!is_array($lignes) || !array_is_list($lignes) || $lignes === [] || count($lignes) > 2000) {
            throw new UnprocessableEntityHttpException('Le CSV doit contenir entre 1 et 2 000 lignes.');
        }

        $enseignements = $this->indexerEnseignements($semestre);
        $aImporter = [];
        foreach ($lignes as $index => $ligne) {
            $numero = $index + 2;
            if (!is_array($ligne) || !is_array($ligne['heures'] ?? null) || !is_array($ligne['groupes'] ?? null)) {
                throw new UnprocessableEntityHttpException("Ligne $numero : format invalide.");
            }
            $personnelId = $ligne['personnel'] ?? null;
            $code = $ligne['enseignement'] ?? null;
            if (!is_int($personnelId) || $personnelId <= 0 || !is_string($code)
                || count($enseignements[trim($code)] ?? []) !== 1) {
                throw new UnprocessableEntityHttpException("Ligne $numero : intervenant ou code d’enseignement invalide ou ambigu.");
            }
            $personnel = $this->em->find(Personnel::class, $personnelId);
            if (!$personnel) {
                throw new UnprocessableEntityHttpException("Ligne $numero : intervenant introuvable.");
            }
            $heures = $groupes = [];
            foreach (self::TYPES as $type) {
                $h = $ligne['heures'][$type] ?? 0;
                $g = $ligne['groupes'][$type] ?? 0;
                if ((!is_float($h) && !is_int($h)) || !is_finite((float) $h) || $h < 0 || !is_int($g) || $g < 0) {
                    throw new UnprocessableEntityHttpException("Ligne $numero : heures ou groupes invalides pour $type.");
                }
                $heures[$type] = $h;
                $groupes[$type] = $g;
            }
            $aImporter[] = $this->construire($semestre, array_values($enseignements[trim($code)])[0], $personnel, $heures, $groupes);
        }

        return $this->json(['ajoutees' => $this->ajouter($semestre, $aImporter)], 201);
    }

    #[Route('/copie', name: 'api_previsionnel_copie', methods: ['POST'])]
    public function copier(#[MapEntity(id: 'id')] StructureSemestre $semestre, Request $request): JsonResponse
    {
        $sourceId = $request->toArray()['source'] ?? null;
        $source = is_int($sourceId) ? $this->em->find(StructureSemestre::class, $sourceId) : null;
        $diplome = $semestre->getAnnee()?->getPn()?->getDiplome();
        if (!$source || $source === $semestre || !$diplome || $source->getAnnee()?->getPn()?->getDiplome() !== $diplome) {
            throw new UnprocessableEntityHttpException('Sélectionnez un autre semestre du même diplôme.');
        }

        $this->denyAccessUnlessGranted('CAN_EDIT_SEMESTRE', $semestre);
        $enseignements = $this->indexerEnseignements($semestre);
        $copies = [];
        foreach ($this->previsionnels->findForSemester($source) as $ligne) {
            $this->denyAccessUnlessGranted('CAN_VIEW_PREVISIONNEL', $ligne);
            $formation = $ligne->getEnseignement();
            $code = trim($formation->getCodeEnseignement() ?? '');
            $cibles = array_values($enseignements[$code] ?? []);
            if (count($cibles) !== 1 || $cibles[0]->getType() !== $formation->getType() || !$ligne->getPersonnel()) {
                throw new UnprocessableEntityHttpException(sprintf('Impossible de faire correspondre l’enseignement « %s » dans le semestre cible.', $formation->getDisplay()));
            }
            $copies[] = $this->construire($semestre, $cibles[0], $ligne->getPersonnel(), $ligne->getHeures(), $ligne->getGroupes());
        }

        if ($copies === []) {
            throw new UnprocessableEntityHttpException('Le semestre source ne contient aucune ligne à copier.');
        }

        return $this->json(['ajoutees' => $this->ajouter($semestre, $copies)], 201);
    }

    private function construire(StructureSemestre $semestre, ScolEnseignement $enseignement, Personnel $personnel, array $heures, array $groupes): Previsionnel
    {
        $this->verifierRattachement($semestre, $enseignement);
        $ligne = (new Previsionnel())
            ->setAnneeUniversitaire($semestre->getAnnee()?->getPn()?->getAnneeUniversitaire())
            ->setEnseignement($enseignement)->setPersonnel($personnel)
            ->setReferent(false)->setHeures($heures)->setGroupes($groupes);
        $this->denyAccessUnlessGranted('CAN_EDIT_PREVISIONNEL', $ligne);
        $violations = $this->validator->validate($ligne);
        if (count($violations)) {
            throw new UnprocessableEntityHttpException($violations[0]->getMessage());
        }
        return $ligne;
    }

    private function verifierRattachement(StructureSemestre $semestre, ScolEnseignement $enseignement): void
    {
        $annee = $semestre->getAnnee()?->getPn()?->getAnneeUniversitaire();
        foreach ($enseignement->getEnseignementUes() as $lien) {
            $autre = $lien->getUe()?->getSemestre();
            if ($autre && $autre !== $semestre && $autre->getAnnee()?->getPn()?->getAnneeUniversitaire() === $annee) {
                throw new ConflictHttpException(sprintf(
                    '« %s » est partagé avec un autre semestre. Gérez cette ligne individuellement.',
                    $enseignement->getDisplay(),
                ));
            }
        }
    }

    /** @return array<string, array<int, ScolEnseignement>> */
    private function indexerEnseignements(StructureSemestre $semestre): array
    {
        $codes = [];
        foreach ($semestre->getUes() as $ue) {
            foreach ($ue->getEnseignementUes() as $lien) {
                $enseignement = $lien->getEnseignement();
                $code = trim($enseignement?->getCodeEnseignement() ?? '');
                if ($code !== '') {
                    $codes[$code][$enseignement->getId()] = $enseignement;
                }
            }
        }
        return $codes;
    }

    /** @param list<Previsionnel> $nouvelles */
    private function ajouter(StructureSemestre $semestre, array $nouvelles): int
    {
        return $this->em->wrapInTransaction(function () use ($semestre, $nouvelles): int {
            $this->em->lock($semestre, LockMode::PESSIMISTIC_WRITE);
            $existantes = [];
            foreach ($this->previsionnels->findForSemester($semestre) as $ligne) {
                $existantes[$ligne->getEnseignement()->getId()][$ligne->getPersonnel()?->getId()] = true;
            }
            foreach ($nouvelles as $ligne) {
                if (isset($existantes[$ligne->getEnseignement()->getId()][$ligne->getPersonnel()->getId()])) {
                    throw new ConflictHttpException(sprintf(
                        'Une ligne existe déjà pour %s et %s.',
                        $ligne->getPersonnel()->getDisplay(),
                        $ligne->getEnseignement()->getDisplay(),
                    ));
                }
                $this->em->persist($ligne);
            }
            return count($nouvelles);
        });
    }
}
