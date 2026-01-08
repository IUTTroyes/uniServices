<?php

namespace App\ApiDto\Edt;

use Symfony\Component\Serializer\Attribute\Groups;

class EdtStatsDto
{
    #[Groups(['edt_stats:read'])]
    protected float $totalHeures = 0.0;

    /**
     * Heures agrégées par type (clé => heures). Exemple: ['CM' => 12.5, 'TD' => 8]
     */
    #[Groups(['edt_stats:read'])]
    protected array $heuresParType = [];

    /**
     * Répartition des heures par type d'activité (tableau d'objets avec pourcentage).
     * Exemple : [ ['type' => 'CM', 'heures' => 12.5, 'pourcentage' => 30.5], ... ]
     */
    #[Groups(['edt_stats:read'])]
    protected array $repartitionTypes = [];

    /**
     * Répartition des heures par semestre.
     * Exemple : [ ['semestre' => 'S1', 'heures' => 12.5, 'pourcentage' => 30.5], ... ]
     */
    #[Groups(['edt_stats:read'])]
    protected array $repartitionSemestres = [];

    /**
     * Répartition des heures par enseignement.
     * Format: { '<libellé>' : { id: <enseignementId|null>, heures: <float> }, ... }
     */
    #[Groups(['edt_stats:read'])]
    protected array $repartitionEnseignements = [];

    /**
     * Répartition des heures par enseignant.
     * Exemple : [ ['enseignant' => 'xxx', 'heures' => 12.5, 'pourcentage' => 30.5], ... ]
     */
    #[Groups(['edt_stats:read'])]
    protected array $repartitionEnseignants = [];

    /**
     * data pour export des heures
     */
    #[Groups(['edt_stats:read'])]
    protected array $export_data = [];

    public function getTotalHeures(): float
    {
        return $this->totalHeures;
    }

    public function setTotalHeures(float $totalHeures): void
    {
        $this->totalHeures = $totalHeures;
    }

    public function getHeuresParType(): array
    {
        return $this->heuresParType;
    }

    public function setHeuresParType(array $heuresParType): void
    {
        $this->heuresParType = $heuresParType;
    }

    public function getRepartitionTypes(): array
    {
        return $this->repartitionTypes;
    }

    public function setRepartitionTypes(array $repartitionTypes): void
    {
        $this->repartitionTypes = $repartitionTypes;
    }

    public function getRepartitionSemestres(): array
    {
        return $this->repartitionSemestres;
    }

    public function setRepartitionSemestres(array $repartitionSemestres): void
    {
        $this->repartitionSemestres = $repartitionSemestres;
    }

    public function getRepartitionEnseignements(): array
    {
        return $this->repartitionEnseignements;
    }

    public function setRepartitionEnseignements(array $repartitionEnseignements): void
    {
        $this->repartitionEnseignements = $repartitionEnseignements;
    }

    public function getRepartitionEnseignants(): array
    {
        return $this->repartitionEnseignants;
    }

    public function setRepartitionEnseignants(array $repartitionEnseignants): void
    {
        $this->repartitionEnseignants = $repartitionEnseignants;
    }

    public function getExportData(): array
    {
        return $this->export_data;
    }

    public function setExportData(array $export_data): void
    {
        $this->export_data = $export_data;
    }

}
