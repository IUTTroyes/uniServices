<?php

namespace App\ApiDto\Previsionnel;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use Symfony\Component\Serializer\Attribute\Groups;

class PrevisionnelPersonnelDto
{
    #[Groups(['previsionnel_personnel:read'])]
    protected int $id;
    #[Groups(['previsionnel_personnel:read'])]
    protected string $libelle;
    #[Groups(['previsionnel_personnel:read'])]
    protected Personnel $personnel;
    #[Groups(['previsionnel_personnel:read'])]
    protected int $idPersonnel;
    #[Groups(['previsionnel_personnel:read'])]
    protected array $heures = [];
    #[Groups(['previsionnel_personnel:read'])]
    protected array $groupes = [];
    #[Groups(['previsionnel_personnel:read'])]
    protected int $idEnseignement;
    #[Groups(['previsionnel_personnel:read'])]
    protected string $codeEnseignement = '';
    #[Groups(['previsionnel_personnel:read'])]
    protected string $libelleEnseignement = '';
    #[Groups(['previsionnel_personnel:read'])]
    protected TypeEnseignementEnum $typeEnseignement;
    #[Groups(['previsionnel_personnel:read'])]
    protected string $statut;
    #[Groups(['previsionnel_personnel:read'])]
    protected StructureAnneeUniversitaire $structureAnneeUniversitaire;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getStructureAnneeUniversitaire(): StructureAnneeUniversitaire
    {
        return $this->structureAnneeUniversitaire;
    }

    public function setStructureAnneeUniversitaire(StructureAnneeUniversitaire $structureAnneeUniversitaire): void
    {
        $this->structureAnneeUniversitaire = $structureAnneeUniversitaire;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function getPersonnel(): Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(Personnel $personnel): void
    {
        $this->personnel = $personnel;
    }

    public function getIdPersonnel(): int
    {
        return $this->idPersonnel;
    }

    public function setIdPersonnel(int $idPersonnel): void
    {
        $this->idPersonnel = $idPersonnel;
    }

    public function getIdEnseignement(): int
    {
        return $this->idEnseignement;
    }

    public function setIdEnseignement(int $idEnseignement): void
    {
        $this->idEnseignement = $idEnseignement;
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
