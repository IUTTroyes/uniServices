<?php

namespace App\ApiDto\Edt;

use Symfony\Component\Serializer\Attribute\Groups;

class EdtStatsPreviDto
{
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
}
