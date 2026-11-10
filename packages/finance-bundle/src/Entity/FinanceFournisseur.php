<?php

namespace FinanceBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
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
use FinanceBundle\State\FinanceReferentielProcessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FinanceFournisseurRepository::class)]
#[ApiResource(
    operations: [
        // Référentiel partagé : consultable par toute personne ayant accès au package, géré par le service financier.
        new GetCollection(security: "is_granted('ROLE_FINANCE_VIEW')", normalizationContext: ['groups' => ['fournisseur:read']],
            order: ['nomFournisseur' => 'ASC'],
            paginationClientItemsPerPage: true,
            paginationMaximumItemsPerPage: 100),
        new Get(security: "is_granted('ROLE_FINANCE_VIEW')", normalizationContext: ['groups' => ['fournisseur:read']]),
        new Post(securityPostDenormalize: "is_granted('ROLE_FINANCE_MANAGER')", denormalizationContext: ['groups' => ['fournisseur:write']],  processor: FinanceReferentielProcessor::class),
        new Patch(security: "is_granted('ROLE_FINANCE_MANAGER')", securityPostDenormalize: "is_granted('ROLE_FINANCE_MANAGER')", denormalizationContext: ['groups' => ['fournisseur:write']],  processor: FinanceReferentielProcessor::class),
        new Delete(security: "is_granted('ROLE_FINANCE_MANAGER')", processor: FinanceReferentielProcessor::class),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['nomFournisseur' => 'ipartial', 'numeroFournisseur' => 'start'])]
class FinanceFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['fournisseur:read', 'finance:read', 'finance:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['fournisseur:read', 'fournisseur:write', 'finance:read', 'finance:list'])]
    private ?string $numeroFournisseur = null; // Code SIFAC (ex: 1650, 1471)

    #[ORM\Column(length: 255)]
    #[Groups(['fournisseur:read', 'fournisseur:write', 'finance:read', 'finance:list'])]
    #[Assert\NotBlank(message: 'La raison sociale est obligatoire.')]
    #[Assert\Length(max: 255)]
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
        $this->nomFournisseur = trim($nomFournisseur);
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
