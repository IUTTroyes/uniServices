<?php

namespace IntranetBundle\Commission\Dto;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureSemestre;

/**
 * Données brutes (indépendantes du mode) nécessaires à la construction d'une commission.
 */
final readonly class CommissionDataset
{
    /**
     * @param EtudiantScolariteSemestre[]            $scolarites
     * @param array<int, array<int, float>>          $moyennesUe          [scolariteSemestreId][ueId] => moyenne
     * @param array<int, array<int, float>>          $moyennesEnseignement [scolariteSemestreId][enseignementId] => moyenne
     */
    public function __construct(
        public StructureSemestre $semestre,
        public array $scolarites,
        public array $moyennesUe,
        public array $moyennesEnseignement,
    ) {
    }
}
