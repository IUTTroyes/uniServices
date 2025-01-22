<?php

namespace App\Entity\Previsionnel;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Entity\Edt\EdtProgression;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Filter\PrevisionnelFilter;
use App\Repository\Previsionnel\PrevisionnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PrevisionnelRepository::class)]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(normalizationContext: ['groups' => ['previsionnel:read']]),
        new GetCollection(
            normalizationContext: ['groups' => ['previsionnel:read']],
        ),
        new Patch(normalizationContext: ['groups' => ['previsionnel:read']]),
    ],
)]
#[ApiFilter(PrevisionnelFilter::class)]
class Previsionnel
{
    public const DUREE_SEANCE = 1.5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'previsionnels')]
    #[Groups(['previsionnel:read'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'previsionnels')]
    #[Groups(['previsionnel:read'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?bool $referent = null;

    #[ORM\Column(type: Types::JSON)]
    private array $heures = [];

    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?int $nbGrCm = null;

    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?int $nbGrTd = null;

    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?int $nbGrTp = null;

    #[ORM\ManyToOne(inversedBy: 'previsionnels')]
    #[Groups(['previsionnel:read'])]
    private ?ScolEnseignement $enseignement = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[Groups(['previsionnel:read'])]
    private ?EdtProgression $progression = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
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

    public function isReferent(): ?bool
    {
        return $this->referent;
    }

    public function setReferent(bool $referent): static
    {
        $this->referent = $referent;

        return $this;
    }

    public function getNbHCm(): ?float
    {
        return $this->nbHCm;
    }

    public function setNbHCm(float $nbHCm): static
    {
        $this->nbHCm = $nbHCm;

        return $this;
    }

    public function getNbHTd(): ?float
    {
        return $this->nbHTd;
    }

    public function setNbHTd(float $nbHTd): static
    {
        $this->nbHTd = $nbHTd;

        return $this;
    }

    public function getNbHTp(): ?float
    {
        return $this->nbHTp;
    }

    public function setNbHTp(float $nbHTp): static
    {
        $this->nbHTp = $nbHTp;

        return $this;
    }

    public function getNbGrCm(): ?int
    {
        return $this->nbGrCm;
    }

    public function setNbGrCm(int $nbGrCm): static
    {
        $this->nbGrCm = $nbGrCm;

        return $this;
    }

    public function getNbGrTd(): ?int
    {
        return $this->nbGrTd;
    }

    public function setNbGrTd(int $nbGrTd): static
    {
        $this->nbGrTd = $nbGrTd;

        return $this;
    }

    public function getNbGrTp(): ?int
    {
        return $this->nbGrTp;
    }

    public function setNbGrTp(int $nbGrTp): static
    {
        $this->nbGrTp = $nbGrTp;

        return $this;
    }

    public function getEnseignement(): ?ScolEnseignement
    {
        return $this->enseignement;
    }

    public function setEnseignement(?ScolEnseignement $enseignement): static
    {
        $this->enseignement = $enseignement;

        return $this;
    }

    public function getProgression(): ?EdtProgression
    {
        return $this->progression;
    }

    public function setProgression(?EdtProgression $progression): static
    {
        $this->progression = $progression;

        return $this;
    }

    #[Groups(['previsionnel:read'])]
    public function getNbSeanceCm(): ?float
    {
        return $this->nbHCm / self::DUREE_SEANCE;
    }

    #[Groups(['previsionnel:read'])]
    public function getNbSeanceTd(): ?float
    {
        return $this->nbHTd / self::DUREE_SEANCE;
    }

    #[Groups(['previsionnel:read'])]
    public function getNbSeanceTp(): ?float
    {
        return $this->nbHTp / self::DUREE_SEANCE;
    }

    public function getHeures(): array
    {
        return $this->heures;
    }

    public function setHeures(array $heures): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->heures = $resolver->resolve($heures);

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'heures' => [
                'CM' =>  0,
                'TD' => 0,
                'TP' => 0,
                'Projet' => 0,
            ],
        ]);

        $resolver->setAllowedTypes('heures', 'array');
    }
}
