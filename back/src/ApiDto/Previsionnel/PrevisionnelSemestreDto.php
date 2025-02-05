<?php

namespace App\ApiDto\Previsionnel;

use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use Symfony\Component\Serializer\Attribute\Groups;

class PrevisionnelSemestreDto
{
    #[Groups(['previsionnel_semestre:read'])]
    protected string $codeEnseignement = '';
    #[Groups(['previsionnel_semestre:read'])]
    protected string $libelleEnseignement = '';
    #[Groups(['previsionnel_semestre:read'])]
    protected TypeEnseignementEnum $typeEnseignement;
    #[Groups(['previsionnel_semestre:read'])]
    protected ?array $personnels = [];
    #[Groups(['previsionnel_semestre:read'])]
    protected array $heures = [];
    #[Groups(['previsionnel_semestre:read'])]
    protected array $groupes = [];

    public function getCodeEnseignement(): string
    {
        return $this->codeEnseignement;
    }

    public function setCodeEnseignement(string $codeEnseignement): void
    {
        $this->codeEnseignement = $codeEnseignement;
    }

    public function getLibelleEnseignement(): string
    {
        return $this->libelleEnseignement;
    }

    public function setLibelleEnseignement(string $libelleEnseignement): void
    {
        $this->libelleEnseignement = $libelleEnseignement;
    }

    public function getTypeEnseignement(): TypeEnseignementEnum
    {
        return $this->typeEnseignement;
    }

    public function setTypeEnseignement(TypeEnseignementEnum $typeEnseignement): void
    {
        $this->typeEnseignement = $typeEnseignement;
    }

    public function getPersonnels(): ?array
    {
        return $this->personnels;
    }

    public function setPersonnels(?array $personnels): void
    {
        $this->personnels = $personnels;
    }

    public function getHeures(): array
    {
        return $this->heures;
    }

    public function setHeures(array $heures): void
    {
        $this->heures = $heures;
    }

    public function getGroupes(): array
    {
        return $this->groupes;
    }

    public function setGroupes(array $groupes): void
    {
        $this->groupes = $groupes;
    }
}
