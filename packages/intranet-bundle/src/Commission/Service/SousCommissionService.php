<?php

declare(strict_types=1);

namespace IntranetBundle\Commission\Service;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\Commission\{SousCommission, SousCommissionRevision, ResultatSousCommission};
use Symfony\Component\HttpKernel\Exception\{ConflictHttpException, UnprocessableEntityHttpException};

final readonly class SousCommissionService
{
    public function __construct(private EntityManagerInterface $em, private CommissionService $calcul, private CommissionWriteContext $context) {}

    private function commission(StructureSemestre $semestre): ?SousCommission
    {
        return $this->em->getRepository(SousCommission::class)->findOneBy(['semestre' => $semestre]);
    }

    private function revision(SousCommission $commission): SousCommissionRevision
    {
        return $this->em->getRepository(SousCommissionRevision::class)->findOneBy(['sousCommission' => $commission, 'numero' => $commission->getNumeroRevision()])
            ?? throw new \LogicException('Révision de sous-commission introuvable.');
    }

    private function serialize(mixed $value): array
    {
        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }

    public function consulter(StructureSemestre $semestre): array
    {
        $commission = $this->commission($semestre);
        if ($commission?->isValidee()) {
            $revision = $this->revision($commission);
            return [...$revision->getVue(), 'validation' => ['etat' => 'validee', 'revision' => $revision->metadata()], 'empreinte' => $revision->getEmpreinte(), 'erreurs' => []];
        }
        [$view, $dataset] = $this->calcul->calculate($semestre);
        $vue = $this->serialize($view);
        $empreinte = hash('sha256', json_encode([$dataset->sources, $vue, $commission?->getNumeroRevision() ?? 0], JSON_THROW_ON_ERROR));
        return [...$vue, 'validation' => ['etat' => 'ouverte', 'numeroRevision' => $commission?->getNumeroRevision() ?? 0], 'empreinte' => $empreinte, 'erreurs' => $dataset->erreurs];
    }

    public function valider(StructureSemestre $semestre, Personnel $auteur, string $empreinte, array $decisions): array
    {
        return $this->context->run(fn () => $this->em->wrapInTransaction(function () use ($semestre, $auteur, $empreinte, $decisions) {
            // Même ligne et même verrou que toutes les écritures affectant les résultats.
            $this->em->refresh($semestre, LockMode::PESSIMISTIC_WRITE);
            $commission = $this->commission($semestre);
            if ($commission?->isValidee()) throw new ConflictHttpException('Le semestre est déjà validé.');
            [$view, $dataset] = $this->calcul->calculate($semestre);
            $vue = $this->serialize($view);
            $courante = hash('sha256', json_encode([$dataset->sources, $vue, $commission?->getNumeroRevision() ?? 0], JSON_THROW_ON_ERROR));
            if (!hash_equals($courante, $empreinte)) throw new ConflictHttpException('Les données ont changé. Rechargez la sous-commission avant de valider.');
            if ($dataset->erreurs !== []) throw new UnprocessableEntityHttpException(implode(' ', $dataset->erreurs));
            $parInscription = [];
            $connues = array_column($vue['rows'], 'scolariteSemestreId');
            foreach ($decisions as $decision) {
                if (!is_array($decision) || !is_int($decision['inscriptionId'] ?? null) || !in_array($decision['inscriptionId'], $connues, true)
                    || isset($parInscription[$decision['inscriptionId']]) || !is_bool($decision['decision'] ?? null)
                    || (isset($decision['motif']) && !is_string($decision['motif']))) {
                    throw new UnprocessableEntityHttpException('Décision du jury invalide, dupliquée ou hors du semestre.');
                }
                $parInscription[$decision['inscriptionId']] = $decision;
            }
            foreach ($vue['rows'] as &$row) {
                $decision = $parInscription[$row['scolariteSemestreId']] ?? ['decision' => $row['decision'], 'motif' => null];
                $motif = isset($decision['motif']) ? trim($decision['motif']) : null;
                if (!is_bool($decision['decision'])) throw new UnprocessableEntityHttpException('Une décision explicite est requise pour chaque résultat sans proposition calculée.');
                if ($decision['decision'] !== $row['decision'] && !$motif) throw new UnprocessableEntityHttpException('Toute dérogation à la proposition calculée nécessite un motif.');
                $row['propositionCalculee'] = $row['decision'];
                $row['decisionJury'] = $decision['decision'];
                $row['motifJury'] = $motif;
            }
            unset($row);
            $vue['statistiques']['validesJury'] = count(array_filter($vue['rows'], static fn ($r) => $r['decisionJury']));
            $commission ??= new SousCommission($semestre);
            $revision = new SousCommissionRevision($commission, $commission->valider(), $auteur, $vue, $dataset->sources, $courante);
            $this->em->persist($commission);
            $this->enregistrerResultats($revision, $dataset->scolarites);
            $semestre->touchResultats();
            $this->em->flush();
            return $this->consulter($semestre);
        }));
    }

    public function rouvrir(StructureSemestre $semestre, Personnel $auteur, int $numeroAttendu, string $motif): array
    {
        if (trim($motif) === '') throw new UnprocessableEntityHttpException('Un motif de réouverture est obligatoire.');
        return $this->context->run(fn () => $this->em->wrapInTransaction(function () use ($semestre, $auteur, $numeroAttendu, $motif) {
            $this->em->refresh($semestre, LockMode::PESSIMISTIC_WRITE);
            $commission = $this->commission($semestre);
            if (!$commission?->isValidee() || $commission->getNumeroRevision() !== $numeroAttendu) throw new ConflictHttpException('La révision à rouvrir n’est plus la révision validée courante.');
            $revision = $this->revision($commission);
            $revision->invalider($auteur, trim($motif));
            $commission->rouvrir();
            foreach ($this->em->getRepository(ResultatSousCommission::class)->findBy(['revision' => $revision]) as $resultat) {
                $inscription = $resultat->getInscription();
                $inscription->setMoyenne(null);
                $inscription->setRang(null);
                $inscription->setDecision(null);
                $inscription->getScolarite()?->setMoyenne(null);
                $inscription->getScolarite()?->setDecision(null);
            }
            $semestre->touchResultats();
            $this->em->flush();
            return $this->consulter($semestre);
        }));
    }

    public function restaurer(StructureSemestre $semestre, Personnel $auteur, int $numeroSource, int $numeroAttendu, string $motif): array
    {
        $motif = trim($motif);
        if ($motif === '') {
            throw new UnprocessableEntityHttpException('Un motif de restauration est obligatoire.');
        }

        return $this->context->run(fn () => $this->em->wrapInTransaction(function () use ($semestre, $auteur, $numeroSource, $numeroAttendu, $motif) {
            $this->em->refresh($semestre, LockMode::PESSIMISTIC_WRITE);
            $commission = $this->commission($semestre);
            if ($commission === null || $commission->getNumeroRevision() !== $numeroAttendu) {
                throw new ConflictHttpException('La sous-commission a changé. Rechargez son historique.');
            }

            $source = $this->em->getRepository(SousCommissionRevision::class)
                ->findOneBy(['sousCommission' => $commission, 'numero' => $numeroSource]);
            if ($source === null || !$source->isInvalidee()) {
                throw new ConflictHttpException('Sélectionnez une ancienne révision de cette sous-commission.');
            }

            $inscriptions = $this->em->getRepository(EtudiantScolariteSemestre::class)->findBy(['semestre' => $semestre], ['id' => 'ASC']);
            $vue = $source->getVue();
            $inscriptionsActuelles = array_map(static fn (EtudiantScolariteSemestre $inscription) => $inscription->getId(), $inscriptions);
            $inscriptionsSource = array_column($vue['rows'], 'scolariteSemestreId');
            sort($inscriptionsSource);
            if ($inscriptionsActuelles !== $inscriptionsSource) {
                throw new ConflictHttpException('La liste des inscriptions a changé depuis cette révision. La restauration est impossible.');
            }

            if ($commission->isValidee()) {
                $this->revision($commission)->invalider($auteur, sprintf('Remplacée par la restauration de la révision %d. %s', $numeroSource, $motif));
            }
            $numero = $commission->valider();
            $revision = new SousCommissionRevision(
                $commission, $numero, $auteur, $vue, $source->getSources(), $source->getEmpreinte(), $source, $motif,
            );
            $this->enregistrerResultats($revision, $inscriptions);
            $semestre->touchResultats();
            $this->em->flush();

            return $this->consulter($semestre);
        }));
    }

    /** @param EtudiantScolariteSemestre[] $scolarites */
    private function enregistrerResultats(SousCommissionRevision $revision, array $scolarites): void
    {
        $this->em->persist($revision);
        $inscriptions = [];
        foreach ($scolarites as $inscription) {
            $inscriptions[$inscription->getId()] = $inscription;
        }
        foreach ($revision->getVue()['rows'] as $row) {
            $inscription = $inscriptions[$row['scolariteSemestreId']];
            $this->em->persist(new ResultatSousCommission($revision, $inscription, $row, $row['decisionJury'], $row['motifJury']));
            $inscription->setMoyenne($row['moyenne']);
            $inscription->setRang($row['rang']);
            $inscription->setDecision($row['decisionJury']);
            $inscription->getScolarite()?->setMoyenne(null);
            $inscription->getScolarite()?->setDecision(null);
        }
    }

    public function historique(StructureSemestre $semestre): array
    {
        $commission = $this->commission($semestre);
        if ($commission === null) return [];
        return array_map(static fn (SousCommissionRevision $r) => [...$r->metadata(), 'vue' => $r->getVue()],
            $this->em->getRepository(SousCommissionRevision::class)->findBy(['sousCommission' => $commission], ['numero' => 'DESC']));
    }
}
