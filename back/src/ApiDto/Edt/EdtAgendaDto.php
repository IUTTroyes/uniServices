<?php

namespace App\ApiDto\Edt;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Personnel;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\Date;

class EdtAgendaDto
{
    #[Groups(['edt_event:read:agenda'])]
    protected int $id;
    #[Groups(['edt_event:read:agenda'])]
    protected int $idEnseignement;
    #[Groups(['edt_event:read:agenda'])]
    protected string $codeEnseignement = '';
    #[Groups(['edt_event:read:agenda'])]
    protected string $libelleEnseignement = '';
    #[Groups(['edt_event:read:agenda'])]
    protected ?string $typeEnseignement;
    #[Groups(['edt_event:read:agenda'])]
    protected ?string $libPersonnel = null;
    #[Groups(['edt_event:read:agenda'])]
    protected int $idPersonnel;
    #[Groups(['edt_event:read:agenda'])]
    protected ?Date $debut = null;
    #[Groups(['edt_event:read:agenda'])]
    protected ?Date $fin = null;
    #[Groups(['edt_event:read:agenda'])]
    protected ?string $libGroupe = null;
    #[Groups(['edt_event:read:agenda'])]
    protected ?string $typeGroupe = null;
    #[Groups(['edt_event:read:agenda'])]
    protected bool $eval = false;
    #[Groups(['edt_event:read:agenda'])]
    protected string $salle = '';
    #[Groups(['edt_event:read:agenda'])]
    protected string $couleur = '';
    #[Groups(['edt_event:read:agenda'])]
    protected StructureAnneeUniversitaire $anneeUniversitaire;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getAnneeUniversitaire(): StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(StructureAnneeUniversitaire $anneeUniversitaire): void
    {
        $this->anneeUniversitaire = $anneeUniversitaire;
    }

    public function getIdEnseignement(): int
    {
        return $this->idEnseignement;
    }

    public function setIdEnseignement(int $idEnseignement): void
    {
        $this->idEnseignement = $idEnseignement;
    }

    public function getIdPersonnel(): int
    {
        return $this->idPersonnel;
    }

    public function setIdPersonnel(int $idPersonnel): void
    {
        $this->idPersonnel = $idPersonnel;
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

    public function getTypeEnseignement(): ?string
    {
        return $this->typeEnseignement;
    }

    public function setTypeEnseignement(?string $typeEnseignement): void
    {
        $this->typeEnseignement = $typeEnseignement;
    }

    public function getDebut(): ?Date
    {
        return $this->debut;
    }

    public function setDebut(?Date $debut): void
    {
        $this->debut = $debut;
    }

    public function getFin(): ?Date
    {
        return $this->fin;
    }

    public function setFin(?Date $fin): void
    {
        $this->fin = $fin;
    }

    public function getLibPersonnel(): ?string
    {
        return $this->libPersonnel;
    }

    public function setLibPersonnel(?string $libPersonnel): void
    {
        $this->libPersonnel = $libPersonnel;
    }

    public function getLibGroupe(): ?string
    {
        return $this->libGroupe;
    }

    public function setLibGroupe(?string $libGroupe): void
    {
        $this->libGroupe = $libGroupe;
    }

    public function isEval(): bool
    {
        return $this->eval;
    }

    public function setEval(bool $eval): void
    {
        $this->eval = $eval;
    }

    public function getSalle(): string
    {
        return $this->salle;
    }

    public function setSalle(string $salle): void
    {
        $this->salle = $salle;
    }

    public function getCouleur(): string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): void
    {
        $this->couleur = $couleur;
    }

    public function getTypeGroupe(): ?string
    {
        return $this->typeGroupe;
    }

    public function setTypeGroupe(?string $typeGroupe): void
    {
        $this->typeGroupe = $typeGroupe;
    }
}
