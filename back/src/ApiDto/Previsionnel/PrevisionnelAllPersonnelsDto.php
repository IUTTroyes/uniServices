<?php

namespace App\ApiDto\Previsionnel;

use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use Symfony\Component\Serializer\Attribute\Groups;

class PrevisionnelAllPersonnelsDto
{
    #[Groups(['previsionnel_all_personnels:read'])]
    protected string $libelle;
    #[Groups(['previsionnel_all_personnels:read'])]
    protected ?Personnel $personnel;
    #[Groups(['previsionnel_all_personnels:read'])]
    protected array $heures = [];
    #[Groups(['previsionnel_all_personnels:read'])]
    protected int $count;

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }



    public function getPersonnel(): Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): void
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

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): void
    {
        $this->libelle = $libelle;
    }
}
