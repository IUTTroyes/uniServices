<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Apc\ApcReferentiel;
use App\Filter\PnFilter;
use App\Repository\Structure\StructurePnRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StructurePnRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['pn:read']]),
        new GetCollection(normalizationContext: ['groups' => ['pn:read']]),
    ]
)]
#[ApiFilter(PnFilter::class)]
class StructurePn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pn:read', 'diplome:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['pn:read', 'diplome:read:full', 'diplome:read'])]
    private string $libelle;

    #[ORM\Column]
    #[Groups(['diplome:read:full'])]
    private int $anneePublication;

    #[ORM\ManyToOne(inversedBy: 'structurePns')]
    private ?StructureDiplome $diplome = null;

    /**
     * @var Collection<int, StructureAnneeUniversitaire>
     */
    #[ORM\ManyToMany(targetEntity: StructureAnneeUniversitaire::class, mappedBy: 'pn')]
    #[Groups(['pn:read'])]
    private Collection $anneeUniversitaires;

    #[ORM\ManyToOne(inversedBy: 'pn')]
    private ?ApcReferentiel $apcReferentiel = null;

    /**
     * @var Collection<int, StructureAnnee>
     */
    #[ORM\OneToMany(targetEntity: StructureAnnee::class, mappedBy: 'pn')]
    #[Groups(['pn:read', 'diplome:read:full'])]
    private Collection $annees;

    public function __construct(StructureDiplome $diplome)
    {
        $this->anneeUniversitaires = new ArrayCollection();
        $this->anneePublication = (int)(new DateTime('now'))->format('Y');
        $this->annees = new ArrayCollection();
        $this->setDiplome($diplome);
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

    public function getAnneePublication(): ?int
    {
        return $this->anneePublication;
    }

    public function setAnneePublication(int $anneePublication): static
    {
        $this->anneePublication = $anneePublication;

        return $this;
    }

    public function getDiplome(): ?StructureDiplome
    {
        return $this->diplome;
    }

    public function setDiplome(?StructureDiplome $diplome): static
    {
        $this->diplome = $diplome;

        return $this;
    }

    /**
     * @return Collection<int, StructureAnneeUniversitaire>
     */
    public function getAnneeUniversitaires(): Collection
    {
        return $this->anneeUniversitaires;
    }

    public function addAnneeUniversitaire(StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        if (!$this->anneeUniversitaires->contains($anneeUniversitaire)) {
            $this->anneeUniversitaires->add($anneeUniversitaire);
            $anneeUniversitaire->addPn($this);
        }

        return $this;
    }

    public function removeAnneeUniversitaire(StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        if ($this->anneeUniversitaires->removeElement($anneeUniversitaire)) {
            $anneeUniversitaire->removePn($this);
        }

        return $this;
    }

    public function getApcReferentiel(): ?ApcReferentiel
    {
        return $this->apcReferentiel;
    }

    public function setApcReferentiel(?ApcReferentiel $apcReferentiel): static
    {
        $this->apcReferentiel = $apcReferentiel;

        return $this;
    }

    /**
     * @return Collection<int, StructureAnnee>
     */
    public function getAnnees(): Collection
    {
        return $this->annees;
    }

    public function addAnnee(StructureAnnee $annee): static
    {
        if (!$this->annees->contains($annee)) {
            $this->annees->add($annee);
            $annee->setPn($this);
        }

        return $this;
    }

    public function removeAnnee(StructureAnnee $annee): static
    {
        if ($this->annees->removeElement($annee)) {
            // set the owning side to null (unless already changed)
            if ($annee->getPn() === $this) {
                $annee->setPn(null);
            }
        }

        return $this;
    }
}
