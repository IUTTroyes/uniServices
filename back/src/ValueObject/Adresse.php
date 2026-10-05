<?php

declare(strict_types=1);

namespace App\ValueObject;

use Symfony\Component\Serializer\Attribute\Groups;

class Adresse implements \JsonSerializable, \Stringable
{
    public const DEFAULT_GROUPS = [
        'etudiant:detail',
        'etudiant:write',
        'personnel:detail',
        'personnel:write',
        'personnel:config',
        'etablissement:read',
        'etablissement:write',
        'stage_entreprise_administration',
        'stage_entreprise',
        'stage_periode_gestion',
        'stage_etudiant:read',
        'stage_etudiant:write',
        'alternance_administration',
        'adresse:read',
        'adresse:write',
        'scolarite:read',
    ];

    #[Groups(self::DEFAULT_GROUPS)]
    private ?string $adresse = null;

    #[Groups(self::DEFAULT_GROUPS)]
    private ?string $complement1 = null;

    #[Groups(self::DEFAULT_GROUPS)]
    private ?string $complement2 = null;

    #[Groups(self::DEFAULT_GROUPS)]
    private ?string $ville = null;

    #[Groups(self::DEFAULT_GROUPS)]
    private ?string $codePostal = null;

    #[Groups(self::DEFAULT_GROUPS)]
    private string $pays = 'France';

    public function __construct(
        ?string $adresse = null,
        ?string $complement1 = null,
        ?string $complement2 = null,
        ?string $ville = null,
        ?string $codePostal = null,
        ?string $pays = 'France'
    ) {
        $this->adresse = $adresse !== null ? trim($adresse) : null;
        $this->complement1 = $complement1 !== null ? trim($complement1) : null;
        $this->complement2 = $complement2 !== null ? trim($complement2) : null;
        $this->ville = $ville !== null ? trim($ville) : null;
        $this->codePostal = $codePostal !== null ? trim($codePostal) : null;
        $this->pays = !empty($pays) ? trim($pays) : 'France';
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse !== null ? trim($adresse) : null;

        return $this;
    }

    public function getComplement1(): ?string
    {
        return $this->complement1;
    }

    public function setComplement1(?string $complement1): self
    {
        $this->complement1 = $complement1 !== null ? trim($complement1) : null;

        return $this;
    }

    public function getComplement2(): ?string
    {
        return $this->complement2;
    }

    public function setComplement2(?string $complement2): self
    {
        $this->complement2 = $complement2 !== null ? trim($complement2) : null;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville !== null ? trim($ville) : null;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal !== null ? trim($codePostal) : null;

        return $this;
    }

    public function getPays(): string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): self
    {
        $this->pays = !empty($pays) ? trim($pays) : 'France';

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->adresse)
            && empty($this->complement1)
            && empty($this->complement2)
            && empty($this->ville)
            && empty($this->codePostal);
    }

    public function getInlineAdresse(): string
    {
        $parts = array_values(array_filter([
            $this->adresse,
            $this->complement1,
            $this->complement2,
            trim(($this->codePostal ?? '') . ' ' . ($this->ville ?? '')),
            ($this->pays && mb_strtolower($this->pays) !== 'france') ? $this->pays : null,
        ], static fn (?string $v) => !empty($v)));

        return implode(', ', $parts);
    }

    public function getFullAdresse(string $separator = "\n"): string
    {
        $lines = array_values(array_filter([
            $this->adresse,
            $this->complement1,
            $this->complement2,
            trim(($this->codePostal ?? '') . ' ' . ($this->ville ?? '')),
            $this->pays,
        ], static fn (?string $v) => !empty($v)));

        return implode($separator, $lines);
    }

    public function __toString(): string
    {
        return $this->getInlineAdresse();
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->adresse === $other->adresse
            && $this->complement1 === $other->complement1
            && $this->complement2 === $other->complement2
            && $this->ville === $other->ville
            && $this->codePostal === $other->codePostal
            && $this->pays === $other->pays;
    }

    public function toArray(): array
    {
        return [
            'adresse' => $this->adresse ?? '',
            'complement1' => $this->complement1 ?? '',
            'complement2' => $this->complement2 ?? '',
            'ville' => $this->ville ?? '',
            'codePostal' => $this->codePostal ?? '',
            'pays' => $this->pays,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(?array $data): ?self
    {
        if ($data === null || empty($data)) {
            return null;
        }

        $adresse = new self(
            $data['adresse'] ?? $data['adresse1'] ?? $data['rue'] ?? $data['LIB_AD1'] ?? null,
            $data['complement1'] ?? $data['adresse2'] ?? $data['complement'] ?? $data['LIB_AD2'] ?? null,
            $data['complement2'] ?? $data['adresse3'] ?? $data['LIB_AD3'] ?? null,
            $data['ville'] ?? $data['commune'] ?? null,
            $data['codePostal'] ?? $data['code_postal'] ?? $data['codepostal'] ?? $data['cp'] ?? null,
            $data['pays'] ?? 'France'
        );

        return $adresse->isEmpty() ? null : $adresse;
    }
}
