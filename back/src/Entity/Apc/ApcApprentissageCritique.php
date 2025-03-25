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
    #[Groups('structure_diplome:read')]
    private ?string $libelle = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups('structure_diplome:read')]
    private ?string $code = null;

    /**
     * @var Collection<int, ScolEnseignement>
     */
    #[ORM\ManyToMany(targetEntity: ScolEnseignement::class, mappedBy: 'apcApprentissageCritique')]
    private Collection $scolEnseignements;

    #[ORM\ManyToOne(inversedBy: 'apcApprentissageCritique')]
    #[Groups('structure_diplome:read')]
    private ?ApcNiveau $apcNiveau = null;

    public function __construct()
    {
        $this->scolEnseignements = new ArrayCollection();
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
    public function getScolEnseignements(): Collection
    {
        return $this->scolEnseignements;
    }

    public function addScolEnseignement(ScolEnseignement $scolEnseignement): static
    {
        if (!$this->scolEnseignements->contains($scolEnseignement)) {
            $this->scolEnseignements->add($scolEnseignement);
            $scolEnseignement->addApcApprentissageCritique($this);
        }

        return $this;
    }

    public function removeScolEnseignement(ScolEnseignement $scolEnseignement): static
    {
        if ($this->scolEnseignements->removeElement($scolEnseignement)) {
            $scolEnseignement->removeApcApprentissageCritique($this);
        }

        return $this;
    }

    public function getApcNiveau(): ?ApcNiveau
    {
        return $this->apcNiveau;
    }

    public function setApcNiveau(?ApcNiveau $apcNiveau): static
    {
        $this->apcNiveau = $apcNiveau;

        return $this;
    }
}
