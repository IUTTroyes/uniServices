<?php

namespace IntranetBundle\Commission\Contract;

use App\Entity\Structure\StructureSemestre;
use IntranetBundle\Commission\Dto\CommissionDataset;
use IntranetBundle\Commission\Dto\CommissionView;
use IntranetBundle\Commission\Enum\CommissionModeEnum;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Stratégie de construction d'une commission pour un type de diplôme donné.
 * Toute implémentation est automatiquement enregistrée (tag) et découverte par le résolveur.
 */
#[AutoconfigureTag(CommissionStrategyInterface::TAG)]
interface CommissionStrategyInterface
{
    public const TAG = 'intranet.commission.strategy';

    public function getMode(): CommissionModeEnum;

    /** Indique si la stratégie gère ce semestre (typiquement selon le type de diplôme). */
    public function supports(StructureSemestre $semestre): bool;

    /** Construit la vue (colonnes, lignes, statistiques) de la commission. */
    public function build(StructureSemestre $semestre, CommissionDataset $dataset): CommissionView;
}
