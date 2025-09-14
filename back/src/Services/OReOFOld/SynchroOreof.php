<?php

namespace App\Services\OReOFOld;

class SynchroOreof
{
    public function __construct(
        protected SynchroDiplomes $synchroDiplomes,
        protected SynchroDiplome $synchroDiplome,
        protected SynchroParcours $synchroParcours
    )
    {
    }

    public function syncAllDiplomes(): SynchroDiplomes
    {
        $this->synchroDiplomes->syncAll();

        return $this->synchroDiplomes;
    }

    public function syncDiplome(int $diplomeId): SynchroDiplome
    {
        $this->synchroDiplome->sync($diplomeId);

        return $this->synchroDiplome;
    }

    public function syncParcours(int $parcoursId): SynchroParcours
    {
        $this->synchroParcours->sync($parcoursId);

        return $this->synchroParcours;
    }
}
