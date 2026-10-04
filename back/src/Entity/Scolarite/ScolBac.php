<?php

namespace App\Entity\Scolarite;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\ApogeeTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Users\Etudiant;
use App\Repository\Scolarite\ScolBacRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ScolBacRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['bac:detail', 'bac:light']]),
        new GetCollection(normalizationContext: ['groups' => ['bac:detail', 'bac:light']]),
        new Post(
            normalizationContext: ['groups' => ['bac:detail', 'bac:light']],
            denormalizationContext: ['groups' => ['bac:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_BAC', object)"
        ),
        new Patch(
            normalizationContext: ['groups' => ['bac:detail', 'bac:light']],
            denormalizationContext: ['groups' => ['bac:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_BAC', object)"
        ),
        new Delete(security: "is_granted('CAN_DELETE_BAC', object)"),
    ],
    order: ['libelle' => 'ASC']
)]
class ScolBac
{
    use ApogeeTrait;
    use OldIdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['bac:light', 'bac:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['bac:light', 'bac:detail', 'scolarite-semestre:manage-groupes', 'bac:write'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
    #[Groups(['bac:light', 'bac:detail', 'bac:write'])]
    private ?string $libelle_long = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $typeBac = null;

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
    public function getTypeBac(): ?string
    {
        return $this->typeBac;
    }

    public function setTypeBac(?string $typeBac): static
    {
        $this->typeBac = $typeBac;

        return $this;
    }

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

    #[Groups(['bac:light', 'bac:detail', 'bac:write'])]
    public function getCodeApogee(): ?string
    {
        return $this->codeApogee;
    }

    #[Groups(['bac:write'])]
    public function setCodeApogee(?string $codeApogee): static
    {
        $this->codeApogee = $codeApogee;

        return $this;
    }
}
