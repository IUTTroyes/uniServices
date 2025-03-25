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
        new Get(normalizationContext: ['groups' => ['structure_pn:read']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_pn:read']]),
    ]
)]
#[ApiFilter(PnFilter::class)]
class StructurePn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['structure_pn:read', 'structure_diplome:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['structure_pn:read', 'structure_diplome:read:full', 'structure_diplome:read'])]
    private string $libelle;

    #[ORM\Column]
    #[Groups(['structure_diplome:read:full'])]
    private int $anneePublication;

    #[ORM\ManyToOne(inversedBy: 'structurePns')]
    private ?StructureDiplome $diplome = null;

    /**
     * @var Collection<int, StructureAnneeUniversitaire>
     */
    #[ORM\ManyToMany(targetEntity: StructureAnneeUniversitaire::class, mappedBy: 'pn')]
    #[Groups(['structure_diplome:read'])]
    private Collection $structureAnneeUniversitaires;

    #[ORM\ManyToOne(inversedBy: 'pn')]
    private ?ApcReferentiel $apcReferentiel = null;

    /**
     * @var Collection<int, StructureAnnee>
     */
    #[ORM\OneToMany(targetEntity: StructureAnnee::class, mappedBy: 'pn')]
    #[Groups(['structure_pn:read', 'structure_diplome:read:full', 'structure_diplome:read'])]
    private Collection $structureAnnees;

    public function __construct(StructureDiplome $diplome)
    {
        $this->structureAnneeUniversitaires = new ArrayCollection();
        $this->anneePublication = (int)(new DateTime('now'))->format('Y');
        $this->structureAnnees = new ArrayCollection();
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
    public function getStructureAnneeUniversitaires(): Collection
    {
        return $this->structureAnneeUniversitaires;
    }

    public function addStructureAnneeUniversitaire(StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        if (!$this->structureAnneeUniversitaires->contains($structureAnneeUniversitaire)) {
            $this->structureAnneeUniversitaires->add($structureAnneeUniversitaire);
            $structureAnneeUniversitaire->addPn($this);
        }

        return $this;
    }

    public function removeStructureAnneeUniversitaire(StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        if ($this->structureAnneeUniversitaires->removeElement($structureAnneeUniversitaire)) {
            $structureAnneeUniversitaire->removePn($this);
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
    public function getStructureAnnees(): Collection
    {
        return $this->structureAnnees;
    }

    public function addStructureAnnee(StructureAnnee $structureAnnee): static
    {
        if (!$this->structureAnnees->contains($structureAnnee)) {
            $this->structureAnnees->add($structureAnnee);
            $structureAnnee->setPn($this);
        }

        return $this;
    }

    public function removeStructureAnnee(StructureAnnee $structureAnnee): static
    {
        if ($this->structureAnnees->removeElement($structureAnnee)) {
            // set the owning side to null (unless already changed)
            if ($structureAnnee->getPn() === $this) {
                $structureAnnee->setPn(null);
            }
        }

        return $this;
    }
}
