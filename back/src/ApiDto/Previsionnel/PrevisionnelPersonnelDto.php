<?php

namespace App\ApiDto\Previsionnel;

use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use Symfony\Component\Serializer\Attribute\Groups;

class PrevisionnelPersonnelDto
{
    #[Groups(['previsionnel_personnel:read'])]
    protected string $libelle;
    #[Groups(['previsionnel_personnel:read'])]
    protected Personnel $personnel;
    #[Groups(['previsionnel_personnel:read'])]
    protected array $heures = [];
    #[Groups(['previsionnel_personnel:read'])]
    protected array $groupes = [];
    #[Groups(['previsionnel_personnel:read'])]
    protected string $codeEnseignement = '';
    #[Groups(['previsionnel_personnel:read'])]
    protected string $libelleEnseignement = '';
    #[Groups(['previsionnel_personnel:read'])]
    protected TypeEnseignementEnum $typeEnseignement;

    public function getPersonnel(): Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(Personnel $personnel): void
    {
        $this->personnel = $personnel;
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

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): void
    {
        $this->libelle = $libelle;
    }


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
}
