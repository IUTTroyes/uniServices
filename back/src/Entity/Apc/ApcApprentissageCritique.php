<?php

namespace App\Entity\Apc;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Traits\OldIdTrait;
use App\Repository\Apc\ApcApprentissageCritiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ApcApprentissageCritiqueRepository::class)]
class ApcApprentissageCritique
{
    use OldIdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['diplome:read', 'enseignement:read'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups('enseignement:read')]
    private ?string $code = null;

    /**
     * @var Collection<int, ScolEnseignement>
     */
    #[ORM\ManyToMany(targetEntity: ScolEnseignement::class, mappedBy: 'apprentissageCritique')]
    private Collection $enseignements;

    #[ORM\ManyToOne(inversedBy: 'apprentissageCritique')]
    #[Groups(['diplome:read', 'enseignement:read'])]
    private ?ApcNiveau $niveau = null;

    public function __construct()
    {
        $this->enseignements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, ScolEnseignement>
     */
    public function getEnseignements(): Collection
    {
        return $this->enseignements;
    }

    public function addEnseignement(ScolEnseignement $enseignement): static
    {
        if (!$this->enseignements->contains($enseignement)) {
            $this->enseignements->add($enseignement);
            $enseignement->addApprentissageCritique($this);
        }

        return $this;
    }

    public function removeEnseignement(ScolEnseignement $enseignement): static
    {
        if ($this->enseignements->removeElement($enseignement)) {
            $enseignement->removeApprentissageCritique($this);
        }

        return $this;
    }

    public function getNiveau(): ?ApcNiveau
    {
        return $this->niveau;
    }

    public function setNiveau(?ApcNiveau $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }
}
