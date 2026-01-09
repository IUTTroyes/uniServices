<?php

namespace App\ApiDto\EtudiantScolariteSemestre;

use Symfony\Component\Serializer\Attribute\Groups;

class EtudiantScolariteSemestreDto
{
    #[Groups(['scolarite-semestre:manage-groupes'])]
    protected array $etudiant = [];

    #[Groups(['scolarite-semestre:manage-groupes'])]
    protected array $groupes = [];

    #[Groups(['scolarite-semestre:manage-groupes'])]
    protected int $id = 0;

    public function getEtudiant(): array
    {
        return $this->etudiant;
    }

    public function setEtudiant(array $etudiant): void
    {
        $this->etudiant = $etudiant;
    }

    public function getGroupes(): array
    {
        return $this->groupes;
    }

    public function setGroupes(array $groupes): void
    {
        $this->groupes = $groupes;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
