<?php

namespace App\Services\OReOFOld;

use App\Repository\Structure\StructureDiplomeRepository;

class SynchroDiplomes
{
    protected array $diplomes;

    public function __construct(
        protected SynchroDiplome $synchroDiplome,
        protected StructureDiplomeRepository $structureDiplomeRepository
    )
    {
    }

    public function syncAll() : void
    {
        // récupération des diplômes
        $this->getDiplomes();
        // Logique pour la synchronisation de tous les diplômes
        foreach ($this->diplomes as $diplome) {
            $this->synchroDiplome->sync($diplome);
        }

    }

    private function getDiplomes(): void
    {
        $this->diplomes = $this->structureDiplomeRepository->findAll();
    }
}
