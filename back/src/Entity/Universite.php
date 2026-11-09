<?php

namespace App\Entity;

use App\ValueObject\Adresse;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Université de rattachement de l'établissement (l'IUT), embarquée dans la table `etablissement`
 * (colonnes `universite_*`). Utilisée par les documents officiels, comme la convention de stage.
 * Pourra devenir une entité si plusieurs universités doivent un jour être gérées.
 */
#[ORM\Embeddable]
class Universite
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $telephone = null;

    /** Président(e) de l'université (prénom et nom). */
    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $president = null;

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = self::nettoyer($libelle);

        return $this;
    }

    #[Groups(['etablissement:read'])]
    public function getAdresse(): ?Adresse
    {
        return Adresse::fromArray($this->adresse);
    }

    #[Groups(['etablissement:write'])]
    public function setAdresse(Adresse|array|null $adresse): static
    {
        if ($adresse instanceof Adresse) {
            $this->adresse = $adresse->toArray();
        } elseif (is_array($adresse)) {
            $this->adresse = Adresse::fromArray($adresse)?->toArray();
        } else {
            $this->adresse = null;
        }

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = self::nettoyer($telephone);

        return $this;
    }

    public function getPresident(): ?string
    {
        return $this->president;
    }

    public function setPresident(?string $president): static
    {
        $this->president = self::nettoyer($president);

        return $this;
    }

    private static function nettoyer(?string $valeur): ?string
    {
        return $valeur !== null && trim($valeur) !== '' ? trim($valeur) : null;
    }
}
