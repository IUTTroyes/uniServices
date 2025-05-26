<?php

namespace App\Services\OReOF;

use App\Entity\Structure\StructureDiplome;

class SynchroOreof
{
    public function __construct(
        protected SynchroReferentielCompetence $synchroReferentielCompetence,
        protected SynchroDiplomes $synchroDiplomes,
        protected SynchroDiplome $synchroDiplome,
        protected SynchroParcours $synchroParcours
    )
    {
    }

    public function syncAllDiplomes()
    {
        $this->synchroDiplomes->syncAll();

        return $this->synchroDiplomes;
    }

    public function syncDiplome(int $diplomeId, int $annee)
    {
        $this->synchroDiplome->sync($diplomeId, $annee);

        return $this->synchroDiplome;
    }

    public function syncParcours(int $parcoursId)
    {
        $this->synchroParcours->sync($parcoursId);

        return $this->synchroParcours;
    }

    public function syncReferentielCompetencesBut(int $referentielId, StructureDiplome $diplome): SynchroDiplome
    {
        $this->synchroReferentielCompetence->syncBut($referentielId, $diplome);

        return $this->synchroDiplome;
    }
}
