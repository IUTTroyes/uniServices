<?php

namespace IntranetBundle\Commission\Strategy;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use IntranetBundle\Commission\Contract\CommissionDecisionRuleInterface;
use IntranetBundle\Commission\Contract\CommissionStrategyInterface;
use IntranetBundle\Commission\Dto\CommissionColumn;
use IntranetBundle\Commission\Dto\CommissionDataset;
use IntranetBundle\Commission\Dto\CommissionRow;
use IntranetBundle\Commission\Dto\CommissionView;

/**
 * Squelette commun : construction des lignes, rang et statistiques.
 * Les stratégies concrètes définissent colonnes, pondération et règle de décision.
 */
abstract class AbstractCommissionStrategy implements CommissionStrategyInterface
{
    public function __construct(private readonly CommissionDecisionRuleInterface $decisionRule)
    {
    }

    /** @return CommissionColumn[] */
    abstract protected function buildColumns(StructureSemestre $semestre): array;

    /** Poids d'une UE dans la moyenne générale (coefficient ou ECTS selon le mode). */
    abstract protected function getUeWeight(StructureUe $ue): float;

    public function build(StructureSemestre $semestre, CommissionDataset $dataset): CommissionView
    {
        $ues = $this->getUes($semestre);
        $weights = [];
        foreach ($ues as $ue) {
            $weights[$ue->getId()] = $this->getUeWeight($ue);
        }

        $rows = [];
        foreach ($dataset->scolarites as $scolarite) {
            $rows[] = $this->buildRow($scolarite, $ues, $weights, $dataset);
        }

        $this->assignRangs($rows);
        usort($rows, static fn (CommissionRow $a, CommissionRow $b) => strcmp($a->nom.$a->prenom, $b->nom.$b->prenom));

        return new CommissionView(
            $this->getMode(),
            (int) $semestre->getId(),
            (string) $semestre->getLibelle(),
            $this->buildColumns($semestre),
            $rows,
            $this->buildStatistiques($rows),
            $this->buildColumnsByEnseignement($semestre),
        );
    }

    /**
     * @param StructureUe[]            $ues
     * @param array<int, float>        $weights
     */
    private function buildRow(EtudiantScolariteSemestre $scolarite, array $ues, array $weights, CommissionDataset $dataset): CommissionRow
    {
        $scolId = (int) $scolarite->getId();
        $cells = [];
        $moyennesUe = [];

        foreach ($ues as $ue) {
            $moyenne = $dataset->moyennesUe[$scolId][$ue->getId()] ?? null;
            $moyennesUe[$ue->getId()] = $moyenne;
            $cells['ue_'.$ue->getId()] = $moyenne;

            foreach ($ue->getEnseignementUes() as $enseignementUe) {
                /** @var ScolEnseignementUe $enseignementUe */
                $enseignementId = $enseignementUe->getEnseignement()?->getId();
                $note = $dataset->moyennesEnseignement[$scolId][$enseignementId] ?? null;
                $cells['ens_'.$ue->getId().'_'.$enseignementId] = $note;
                if (null !== $enseignementId) {
                    $cells['ens_'.$enseignementId] = $note;
                }
            }
        }

        $moyenneGenerale = $scolarite->getMoyenne() ?? $this->computeMoyenneGenerale($moyennesUe, $weights);
        $decision = $this->decisionRule->decide($moyennesUe, $weights, $moyenneGenerale);
        $etudiant = $scolarite->getScolarite()?->getEtudiant();
        $dateNaissance = $etudiant?->getDateNaissance()?->format('d/m/Y');
        $numEtudiant = $etudiant?->getNumEtudiant();
        $bac = $etudiant?->getBac()?->getLibelle();

        return new CommissionRow(
            $scolId,
            $etudiant?->getId(),
            (string) $etudiant?->getNom(),
            (string) $etudiant?->getPrenom(),
            $cells,
            $moyenneGenerale,
            null,
            $decision['decision'],
            $decision['libelle'],
            $decision['uesValidees'],
            $numEtudiant,
            $dateNaissance,
            $bac,
        );
    }

    /**
     * @param array<int, float|null> $moyennesUe
     * @param array<int, float>      $weights
     */
    protected function computeMoyenneGenerale(array $moyennesUe, array $weights): ?float
    {
        $somme = 0.0;
        $total = 0.0;
        foreach ($moyennesUe as $ueId => $moyenne) {
            if (null === $moyenne) {
                continue;
            }
            $somme += $moyenne * ($weights[$ueId] ?? 0);
            $total += $weights[$ueId] ?? 0;
        }

        return $total > 0 ? round($somme / $total, 3) : null;
    }

    /** @return StructureUe[] */
    protected function getUes(StructureSemestre $semestre): array
    {
        $ues = array_filter(
            $semestre->getUes()->toArray(),
            static fn (StructureUe $ue) => false !== $ue->isActif(),
        );
        usort($ues, static fn (StructureUe $a, StructureUe $b) => $a->getNumero() <=> $b->getNumero());

        return $ues;
    }

    /** @param CommissionRow[] $rows */
    private function assignRangs(array $rows): void
    {
        $classes = array_filter($rows, static fn (CommissionRow $r) => null !== $r->moyenne);
        usort($classes, static fn (CommissionRow $a, CommissionRow $b) => $b->moyenne <=> $a->moyenne);

        $rang = 0;
        $previous = null;
        foreach ($classes as $index => $row) {
            if ($row->moyenne !== $previous) {
                $rang = $index + 1;
                $previous = $row->moyenne;
            }
            $row->rang = $rang; // ex-aequo : même rang
        }
    }

    /**
     * @param CommissionRow[] $rows
     *
     * @return array<string, mixed>
     */
    protected function buildStatistiques(array $rows): array
    {
        $moyennes = array_values(array_filter(array_map(static fn (CommissionRow $r) => $r->moyenne, $rows), static fn ($m) => null !== $m));
        $valides = count(array_filter($rows, static fn (CommissionRow $r) => true === $r->decision));

        return [
            'effectif' => count($rows),
            'moyennePromo' => [] === $moyennes ? null : round(array_sum($moyennes) / count($moyennes), 2),
            'min' => [] === $moyennes ? null : min($moyennes),
            'max' => [] === $moyennes ? null : max($moyennes),
            'valides' => $valides,
            'tauxReussite' => [] === $rows ? null : round($valides / count($rows) * 100, 1),
        ];
    }

    /** Colonnes UE + enseignements de chaque UE (utilitaire pour les stratégies concrètes). */
    protected function buildColumnsForUes(StructureSemestre $semestre, bool $withEcts): array
    {
        $columns = [];
        foreach ($this->getUes($semestre) as $ue) {
            $ueNumero = (int) $ue->getNumero();
            $ueColor = self::getUeColor($ueNumero);
            $ueKey = 'ue_'.$ue->getId();
            $columns[] = new CommissionColumn(
                $ueKey,
                'UE'.$ueNumero,
                CommissionColumn::TYPE_UE,
                null,
                $ue->getCoefficient(),
                $withEcts ? $ue->getNbEcts() : null,
                $ue->getLibelle(),
                'ue',
                null,
                null,
                $ueColor,
            );

            foreach ($ue->getEnseignementUes() as $enseignementUe) {
                $enseignement = $enseignementUe->getEnseignement();
                $nature = $enseignement?->getType()?->value ?? 'matiere';
                $columns[] = new CommissionColumn(
                    'ens_'.$ue->getId().'_'.$enseignement?->getId(),
                    $enseignement?->getCodeEnseignement() ?? $enseignement?->getLibelleCourt() ?? '',
                    CommissionColumn::TYPE_ENSEIGNEMENT,
                    $ueKey,
                    $enseignementUe->getCoefficient(),
                    $withEcts ? $enseignementUe->getEcts() : null,
                    $enseignement?->getLibelle(),
                    $nature,
                    $enseignement?->getId(),
                );
            }
        }

        return $columns;
    }

    /**
     * Colonnes à plat par Ressource / SAÉ (mode spécifique APC / BUT).
     *
     * @return CommissionColumn[]
     */
    protected function buildColumnsByEnseignement(StructureSemestre $semestre): array
    {
        return [];
    }

    public static function getUeColor(int $numero): string
    {
        $colors = [
            1 => 'emerald',
            2 => 'blue',
            3 => 'purple',
            4 => 'amber',
            5 => 'pink',
            6 => 'teal',
            7 => 'indigo',
            8 => 'rose',
        ];

        return $colors[$numero] ?? 'blue';
    }
}
