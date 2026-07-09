<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Scolarite\ScolEvaluationRattrapage;
use App\Repository\SalleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SalleRepository::class)]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(normalizationContext: ['groups' => ['salle:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['salle:detail']]),
        new Post(securityPostDenormalize: "is_granted('CAN_EDIT_SALLE', object)"),
        new Patch(securityPostDenormalize: "is_granted('CAN_EDIT_SALLE', object)"),
        new Delete(security: "is_granted('CAN_DELETE_SALLE', object)"),
    ],
    order: ['libelle' => 'ASC'],
)]
class Salle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['salle:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['salle:detail'])]
    private ?string $libelle = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['salle:detail'])]
    private ?int $capacite = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['salle:detail'])]
    private ?string $type = null;

    /**
     * @var Collection<int, ScolEvaluationRattrapage>
     */
    #[ORM\OneToMany(targetEntity: ScolEvaluationRattrapage::class, mappedBy: 'salle')]
    private Collection $scolEvaluationRattrapages;

    public function __construct()
    {
        $this->scolEvaluationRattrapages = new ArrayCollection();
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

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, ScolEvaluationRattrapage>
     */
    public function getScolEvaluationRattrapages(): Collection
    {
        return $this->scolEvaluationRattrapages;
    }

    public function addScolEvaluationRattrapage(ScolEvaluationRattrapage $scolEvaluationRattrapage): static
    {
        if (!$this->scolEvaluationRattrapages->contains($scolEvaluationRattrapage)) {
            $this->scolEvaluationRattrapages->add($scolEvaluationRattrapage);
            $scolEvaluationRattrapage->setSalle($this);
        }

        return $this;
    }

    public function removeScolEvaluationRattrapage(ScolEvaluationRattrapage $scolEvaluationRattrapage): static
    {
        if ($this->scolEvaluationRattrapages->removeElement($scolEvaluationRattrapage)) {
            // set the owning side to null (unless already changed)
            if ($scolEvaluationRattrapage->getSalle() === $this) {
                $scolEvaluationRattrapage->setSalle(null);
            }
        }

        return $this;
    }
}
