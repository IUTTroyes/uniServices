<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\StructurePnRepository;
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
class StructurePn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['structure_pn:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\Column]
    private int $anneePublication;

    #[ORM\ManyToOne(inversedBy: 'structurePns')]
    private ?StructureDiplome $diplome = null;

    #[ORM\ManyToOne(inversedBy: 'pn')]
    private ?StructureAnnee $structureAnnee = null;

    /**
     * @var Collection<int, StructureAnneeUniversitaire>
     */
    #[ORM\ManyToMany(targetEntity: StructureAnneeUniversitaire::class, mappedBy: 'pn')]
    private Collection $structureAnneeUniversitaires;

    public function __construct()
    {
        $this->structureAnneeUniversitaires = new ArrayCollection();
        $this->anneePublication = (int)(new DateTime('now'))->format('Y');
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

    public function getStructureAnnee(): ?StructureAnnee
    {
        return $this->structureAnnee;
    }

    public function setStructureAnnee(?StructureAnnee $structureAnnee): static
    {
        $this->structureAnnee = $structureAnnee;

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
}
