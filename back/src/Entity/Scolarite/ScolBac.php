<?php

namespace App\Entity\Scolarite;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\ApogeeTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Users\Etudiant;
use App\Repository\Scolarite\ScolBacRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ScolBacRepository::class)]
#[ApiResource]
class ScolBac
{
    use ApogeeTrait;
    use OldIdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('bac:light')]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups('bac:light')]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle_long = null;

    /**
     * @var Collection<int, Etudiant>
     */
    #[ORM\OneToMany(targetEntity: Etudiant::class, mappedBy: 'bac')]
    private Collection $etudiants;

    public function __construct()
    {
        $this->etudiants = new ArrayCollection();
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

    public function getLibelleLong(): ?string
    {
        return $this->libelle_long;
    }

    public function setLibelleLong(string $libelle_long): static
    {
        $this->libelle_long = $libelle_long;

        return $this;
    }

    /**
     * @return Collection<int, Etudiant>
     */
    public function getEtudiants(): Collection
    {
        return $this->etudiants;
    }

    public function addEtudiant(Etudiant $etudiant): static
    {
        if (!$this->etudiants->contains($etudiant)) {
            $this->etudiants->add($etudiant);
            $etudiant->setBac($this);
        }

        return $this;
    }

    public function removeEtudiant(Etudiant $etudiant): static
    {
        if ($this->etudiants->removeElement($etudiant)) {
            // set the owning side to null (unless already changed)
            if ($etudiant->getBac() === $this) {
                $etudiant->setBac(null);
            }
        }

        return $this;
    }
}
