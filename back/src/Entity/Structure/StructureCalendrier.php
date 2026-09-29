<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Edt\EdtCreneauxInterditsSemaine;
use App\Filter\SemaineFormationFilter;
use App\Repository\Structure\StructureCalendrierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureCalendrierRepository::class)]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(normalizationContext: ['groups' => ['calendrier:detail', 'annee_univ:light']]),
        new GetCollection(normalizationContext: ['groups' => ['calendrier:detail', 'annee_univ:light']]),
        new Post(
            normalizationContext: ['groups' => ['calendrier:detail', 'annee_univ:light']],
            denormalizationContext: ['groups' => ['calendrier:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_CALENDRIER', object)"
        ),
        new Patch(
            normalizationContext: ['groups' => ['calendrier:detail', 'annee_univ:light']],
            denormalizationContext: ['groups' => ['calendrier:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_CALENDRIER', object)"
        ),
        new Delete(security: "is_granted('CAN_DELETE_CALENDRIER', object)"),
    ],
    order: ['semaineFormation' => 'ASC']
)]
#[ApiFilter(SearchFilter::class, properties: ['semaineReelle' => 'exact', 'anneeUniversitaire' => 'exact'])]
class StructureCalendrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['calendrier:detail', 'calendrier:light'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'calendriers')]
    #[Groups(['calendrier:detail', 'calendrier:write'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\Column]
    #[Groups(['calendrier:detail', 'calendrier:light', 'calendrier:write'])]
    private ?int $semaineFormation = null;

    #[ORM\Column]
    #[Groups(['calendrier:detail', 'calendrier:light', 'calendrier:write'])]
    private ?int $semaineReelle = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['calendrier:detail', 'calendrier:light', 'calendrier:write'])]
    private ?\DateTimeInterface $dateLundi = null;

    /**
     * @var Collection<int, EdtCreneauxInterditsSemaine>
     */
    #[ORM\OneToMany(targetEntity: EdtCreneauxInterditsSemaine::class, mappedBy: 'semaine')]
    private Collection $creneauxInterditsSemaines;

    public function __construct()
    {
        $this->creneauxInterditsSemaines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

        return $this;
    }

    public function getSemaineFormation(): ?int
    {
        return $this->semaineFormation;
    }

    public function setSemaineFormation(int $semaineFormation): static
    {
        $this->semaineFormation = $semaineFormation;

        return $this;
    }

    public function getSemaineReelle(): ?int
    {
        return $this->semaineReelle;
    }

    public function setSemaineReelle(int $semaineReelle): static
    {
        $this->semaineReelle = $semaineReelle;

        return $this;
    }

    public function getDateLundi(): ?\DateTimeInterface
    {
        return $this->dateLundi;
    }

    public function setDateLundi(\DateTimeInterface $dateLundi): static
    {
        $this->dateLundi = $dateLundi;

        return $this;
    }

    public function getJours(): array
    {
        $jours = [];
        $date = clone $this->dateLundi;
        for ($i = 0; $i < 5; $i++) {
            $jours[] = clone $date;
            $date->modify('+1 day');
        }

        return $jours;
    }

    /**
     * @return Collection<int, EdtCreneauxInterditsSemaine>
     */
    public function getCreneauxInterditsSemaines(): Collection
    {
        return $this->creneauxInterditsSemaines;
    }

    public function addCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $creneauxInterditsSemaine): static
    {
        if (!$this->creneauxInterditsSemaines->contains($creneauxInterditsSemaine)) {
            $this->creneauxInterditsSemaines->add($creneauxInterditsSemaine);
            $creneauxInterditsSemaine->setSemaine($this);
        }

        return $this;
    }

    public function removeCreneauxInterditsSemaine(EdtCreneauxInterditsSemaine $creneauxInterditsSemaine): static
    {
        if ($this->creneauxInterditsSemaines->removeElement($creneauxInterditsSemaine)) {
            // set the owning side to null (unless already changed)
            if ($creneauxInterditsSemaine->getSemaine() === $this) {
                $creneauxInterditsSemaine->setSemaine(null);
            }
        }

        return $this;
    }
}
