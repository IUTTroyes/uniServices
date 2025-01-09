<?php

namespace App\Services\OReOF;

class SynchroOreof
{
    public function __construct(
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

    public function syncDiplome(int $diplomeId)
    {
        $this->synchroDiplome->sync($diplomeId);

        return $this->synchroDiplome;
    }

    public function syncParcours(int $parcoursId)
    {
        $this->synchroParcours->sync($parcoursId);

        return $this->synchroParcours;
    }
}
