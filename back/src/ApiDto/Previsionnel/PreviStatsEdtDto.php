<?php

namespace App\ApiDto\Previsionnel;

use Symfony\Component\Serializer\Attribute\Groups;

class PreviStatsEdtDto
{
    #[Groups(['previsionnel_stats_edt:read'])]
    protected array $statPreviEdtEnseignement = [];

    #[Groups(['previsionnel_stats_edt:read'])]
    protected array $statPreviEdtEnseignant = [];

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

}
