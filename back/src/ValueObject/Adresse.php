<?php

namespace App\ValueObject;

use Symfony\Component\Serializer\Annotation\Groups;

class Adresse
{
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $adresse;

    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $complement1;

    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $complement2;

    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $ville;

    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $codePostal;

    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $pays;

    public function __construct(string $adresse, string $complement1, string $complement2, string $ville, string $codePostal, string $pays = 'France')
    {
        $this->adresse = $adresse;
        $this->complement1 = $complement1;
        $this->complement2 = $complement2;
        $this->ville = $ville;
        $this->codePostal = $codePostal;
        $this->pays = $pays;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function getComplement1(): string
    {
        return $this->complement1;
    }

    public function getComplement2(): string
    {
        return $this->complement2;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function getPays(): string
    {
        return $this->pays;
    }


    public function toArray(): array
    {
        return [
            'adresse' => $this->adresse,
            'complement1' => $this->complement1,
            'complement2' => $this->complement2,
            'ville' => $this->ville,
            'codePostal' => $this->codePostal,
            'pays' => $this->pays,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['adresse'],
            $data['complement1'],
            $data['complement2'],
            $data['ville'],
            $data['codePostal'],
            $data['pays'],
        );
    }
}
