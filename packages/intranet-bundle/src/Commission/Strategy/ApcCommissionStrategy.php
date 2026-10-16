<?php

namespace IntranetBundle\Commission\Strategy;

use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use IntranetBundle\Commission\Decision\ApcDecisionRule;
use IntranetBundle\Commission\Dto\CommissionColumn;
use IntranetBundle\Commission\Enum\CommissionModeEnum;

/** Commission BUT (APC) : colonnes UE + ressources/SAÉ avec ECTS, pondération par ECTS. */
final class ApcCommissionStrategy extends AbstractCommissionStrategy
{
    public function __construct(ApcDecisionRule $decisionRule)
    {
        parent::__construct($decisionRule);
    }

    public function getMode(): CommissionModeEnum
    {
        return CommissionModeEnum::APC;
    }

    public function supports(StructureSemestre $semestre): bool
    {
        return true === $semestre->getAnnee()?->getTypeDiplome()?->isApc();
    }

    protected function buildColumns(StructureSemestre $semestre): array
    {
        $columns = [];
        foreach ($this->getUes($semestre) as $ue) {
            $ueNumero = (int) $ue->getNumero();
            $competence = $ue->getCompetence();
            $compNomCourt = $competence?->getNomCourt();
            $compCouleur = $competence?->getCouleur();
            $ueColor = self::resolveCompetenceColor($compCouleur, $ueNumero);

            $ueKey = 'ue_'.$ue->getId();
            $columns[] = new CommissionColumn(
                $ueKey,
                'UE'.$ueNumero,
                CommissionColumn::TYPE_UE,
                null,
                $ue->getCoefficient(),
                $ue->getNbEcts(),
                $ue->getLibelle(),
                'ue',
                null,
                null,
                $ueColor,
                $compNomCourt,
                $compCouleur,
            );

            foreach ($ue->getEnseignementUes() as $enseignementUe) {
                $enseignement = $enseignementUe->getEnseignement();
                $nature = $enseignement?->getType()?->value ?? 'ressource';
                $columns[] = new CommissionColumn(
                    'ens_'.$ue->getId().'_'.$enseignement?->getId(),
                    $enseignement?->getCodeEnseignement() ?? $enseignement?->getLibelleCourt() ?? '',
                    CommissionColumn::TYPE_ENSEIGNEMENT,
                    $ueKey,
                    $enseignementUe->getCoefficient(),
                    $enseignementUe->getEcts(),
                    $enseignement?->getLibelle(),
                    $nature,
                    $enseignement?->getId(),
                    null,
                    $ueColor,
                    $compNomCourt,
                    $compCouleur,
                );
            }
        }

        return $columns;
    }

    protected function getUeWeight(StructureUe $ue): float
    {
        return (float) $ue->getNbEcts();
    }

    /**
     * Construit les colonnes uniques par Ressource / SAÉ avec leurs rattachements aux UEs.
     *
     * @return CommissionColumn[]
     */
    protected function buildColumnsByEnseignement(StructureSemestre $semestre): array
    {
        $ues = $this->getUes($semestre);

        /** @var array<int, array{ens: \App\Entity\Scolarite\ScolEnseignement, ues: array<int, array<string, mixed>>}> $itemsMap */
        $itemsMap = [];

        foreach ($ues as $ue) {
            $ueNumero = (int) $ue->getNumero();
            $competence = $ue->getCompetence();
            $compNomCourt = $competence?->getNomCourt();
            $compCouleur = $competence?->getCouleur();
            $ueColor = self::resolveCompetenceColor($compCouleur, $ueNumero);

            foreach ($ue->getEnseignementUes() as $ensUe) {
                $ens = $ensUe->getEnseignement();
                if (null === $ens || null === $ens->getId()) {
                    continue;
                }

                $ensId = (int) $ens->getId();
                if (!isset($itemsMap[$ensId])) {
                    $itemsMap[$ensId] = [
                        'ens' => $ens,
                        'ues' => [],
                    ];
                }

                $itemsMap[$ensId]['ues'][] = [
                    'id' => (int) $ue->getId(),
                    'numero' => $ueNumero,
                    'label' => 'UE'.$ueNumero,
                    'libelle' => (string) $ue->getLibelle(),
                    'coefficient' => (float) $ensUe->getCoefficient(),
                    'ects' => (float) $ensUe->getEcts(),
                    'color' => $ueColor,
                    'competenceNomCourt' => $compNomCourt,
                    'competenceCouleur' => $compCouleur,
                ];
            }
        }

        // Tri : Ressources d'abord, puis SAÉ, puis tri naturel sur le code de l'enseignement
        $items = array_values($itemsMap);
        usort($items, static function (array $a, array $b) {
            $ensA = $a['ens'];
            $ensB = $b['ens'];

            $natureA = $ensA->getType()?->value ?? 'ressource';
            $natureB = $ensB->getType()?->value ?? 'ressource';

            if ($natureA !== $natureB) {
                // 'ressource' ('r') avant 'sae' ('s')
                return strcmp($natureA, $natureB);
            }

            return strnatcasecmp((string) ($ensA->getCodeEnseignement() ?? $ensA->getLibelleCourt()), (string) ($ensB->getCodeEnseignement() ?? $ensB->getLibelleCourt()));
        });

        $columns = [];

        // 1. Colonnes des Ressources & SAÉ
        foreach ($items as $item) {
            $ens = $item['ens'];
            $nature = $ens->getType()?->value ?? 'ressource';

            $columns[] = new CommissionColumn(
                'ens_'.$ens->getId(),
                $ens->getCodeEnseignement() ?? $ens->getLibelleCourt() ?? (string) $ens->getLibelle(),
                CommissionColumn::TYPE_ENSEIGNEMENT,
                null,
                null,
                null,
                $ens->getLibelle(),
                $nature,
                $ens->getId(),
                $item['ues'],
            );
        }

        // 2. Colonnes récapitulatives des UEs (moyenne + statut pour chaque UE avec la compétence)
        foreach ($ues as $ue) {
            $ueNumero = (int) $ue->getNumero();
            $competence = $ue->getCompetence();
            $compNomCourt = $competence?->getNomCourt();
            $compCouleur = $competence?->getCouleur();
            $ueColor = self::resolveCompetenceColor($compCouleur, $ueNumero);

            $columns[] = new CommissionColumn(
                'ue_'.$ue->getId(),
                'UE'.$ueNumero,
                CommissionColumn::TYPE_UE,
                null,
                $ue->getCoefficient(),
                $ue->getNbEcts(),
                $ue->getLibelle(),
                'ue',
                null,
                null,
                $ueColor,
                $compNomCourt,
                $compCouleur,
            );
        }

        return $columns;
    }

    public static function resolveCompetenceColor(?string $couleurCode, int $fallbackNumero): string
    {
        if (null !== $couleurCode && '' !== trim($couleurCode)) {
            $c = strtolower(trim($couleurCode));
            return match ($c) {
                'c1' => 'blue',
                'c2' => 'emerald',
                'c3' => 'amber',
                'c4' => 'rose',
                'c5' => 'purple',
                'c6' => 'cyan',
                default => $c,
            };
        }

        return self::getUeColor($fallbackNumero);
    }
}
