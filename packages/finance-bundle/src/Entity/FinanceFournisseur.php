<?php

namespace FinanceBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FinanceBundle\Repository\FinanceFournisseurRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FinanceFournisseurRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['fournisseur:read']]),
        new Get(normalizationContext: ['groups' => ['fournisseur:read']]),
        new Post(denormalizationContext: ['groups' => ['fournisseur:write']]),
        new Patch(denormalizationContext: ['groups' => ['fournisseur:write']]),
        new Delete()
    ]
)]
class FinanceFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['fournisseur:read', 'finance:read', 'finance:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['fournisseur:read', 'fournisseur:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?string $numeroFournisseur = null; // Code SIFAC (ex: 1650, 1471)

    #[ORM\Column(length: 255)]
    #[Groups(['fournisseur:read', 'fournisseur:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?string $nomFournisseur = null; // Raison sociale (ex: ELITE SECURITE, TOUSSAINT)

    /**
     * @var Collection<int, FinanceBonCommande>
     */
    #[ORM\OneToMany(targetEntity: FinanceBonCommande::class, mappedBy: 'fournisseur')]
    private Collection $bonsCommande;

    public function __construct()
    {
        $this->bonsCommande = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroFournisseur(): ?string
    {
        return $this->numeroFournisseur;
    }

    public function setNumeroFournisseur(?string $numeroFournisseur): static
    {
        $this->numeroFournisseur = $numeroFournisseur;
        return $this;
    }

    public function getNomFournisseur(): ?string
    {
        return $this->nomFournisseur;
    }

    public function setNomFournisseur(string $nomFournisseur): static
    {
        $this->nomFournisseur = $nomFournisseur;
        return $this;
    }

    /**
     * @return Collection<int, FinanceBonCommande>
     */
    public function getBonsCommande(): Collection
    {
        return $this->bonsCommande;
    }

    public function addBonCommande(FinanceBonCommande $bonCommande): static
    {
        if (!$this->bonsCommande->contains($bonCommande)) {
            $this->bonsCommande->add($bonCommande);
            $bonCommande->setFournisseur($this);
        }
        return $this;
    }

    public function removeBonCommande(FinanceBonCommande $bonCommande): static
    {
        if ($this->bonsCommande->removeElement($bonCommande)) {
            if ($bonCommande->getFournisseur() === $this) {
                $bonCommande->setFournisseur(null);
            }
        }
        return $this;
    }
}
