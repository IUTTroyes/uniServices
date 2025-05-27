<?php

namespace App\Entity\Personnel;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\TypeHrsEnum;
use App\Repository\PersonnelEnseignantTypeHrsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PersonnelEnseignantTypeHrsRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['enseignant_hrs:read']]),
    ]
)]
class PersonnelEnseignantTypeHrs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['enseignant_hrs:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['enseignant_hrs:read'])]
    private ?string $libelle = null;

    /**
     * @var Collection<int, PersonnelEnseignantHrs>
     */
    #[ORM\OneToMany(targetEntity: PersonnelEnseignantHrs::class, mappedBy: 'enseignantTypeHrs')]
    private Collection $enseignantHrs;

    #[ORM\Column]
    #[Groups(['enseignant_hrs:read'])]
    private ?bool $incluService = null;

    #[ORM\Column]
    #[Groups(['enseignant_hrs:read'])]
    private ?float $maximum = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, enumType: TypeHrsEnum::class)]
    #[Groups(['enseignant_hrs:read'])]
    private TypeHrsEnum|null $type = null;

    public function __construct()
    {
        $this->enseignantHrs = new ArrayCollection();
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

    /**
     * @return Collection<int, PersonnelEnseignantHrs>
     */
    public function getEnseignantHrs(): Collection
    {
        return $this->enseignantHrs;
    }

    public function addEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if (!$this->enseignantHrs->contains($enseignantHr)) {
            $this->enseignantHrs->add($enseignantHr);
            $enseignantHr->setEnseignantTypeHrs($this);
        }

        return $this;
    }

    public function removeEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if ($this->enseignantHrs->removeElement($enseignantHr)) {
            // set the owning side to null (unless already changed)
            if ($enseignantHr->getEnseignantTypeHrs() === $this) {
                $enseignantHr->setEnseignantTypeHrs(null);
            }
        }

        return $this;
    }

    public function isIncluService(): ?bool
    {
        return $this->incluService;
    }

    public function setIncluService(bool $incluService): static
    {
        $this->incluService = $incluService;

        return $this;
    }

    public function getMaximum(): ?float
    {
        return $this->maximum;
    }

    public function setMaximum(float $maximum): static
    {
        $this->maximum = $maximum;

        return $this;
    }

    public function getType(): ?TypeHrsEnum
    {
        return $this->type;
    }

    public function setType(?TypeHrsEnum $type): void
    {
        $this->type = $type;
    }
}
