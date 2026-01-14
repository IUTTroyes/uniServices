<?php

namespace App\ApiDto\Previsionnel;

use Symfony\Component\Serializer\Attribute\Groups;

class PreviStatsEdtDto
{
    #[Groups(['previsionnel_stats_edt:read'])]
    protected array $statPreviEdtEnseignement = [];

    #[Groups(['previsionnel_stats_edt:read'])]
    protected array $statPreviEdtEnseignant = [];

    // Liste dynamique des types de groupes (CM, TD, TP, ...)
    #[Groups(['previsionnel_stats_edt:read'])]
    protected array $typesGroupes = [];

    #[Groups(['previsionnel_stats_edt:read'])]
    protected int $taux_realisation = 0;

    public function getStatPreviEdtEnseignement(): array
    {
        return $this->statPreviEdtEnseignement;
    }

    public function setStatPreviEdtEnseignement(array $statPreviEdtEnseignement): void
    {
        $this->statPreviEdtEnseignement = $statPreviEdtEnseignement;
    }

    public function getStatPreviEdtEnseignant(): array
    {
        return $this->statPreviEdtEnseignant;
    }

    public function setStatPreviEdtEnseignant(array $statPreviEdtEnseignant): void
    {
        $this->statPreviEdtEnseignant = $statPreviEdtEnseignant;
    }

    public function getTypesGroupes(): array
    {
        return $this->typesGroupes;
    }

    public function setTypesGroupes(array $typesGroupes): void
    {
        $this->typesGroupes = $typesGroupes;
    }

    public function getTauxRealisation(): int
    {
        return $this->taux_realisation;
    }

    public function setTauxRealisation(int $taux_realisation): void
    {
        $this->taux_realisation = $taux_realisation;
    }

}
