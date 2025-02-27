<?php

namespace App\Entity\Previsionnel;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use App\ApiDto\Previsionnel\PrevisionnelAllPersonnelsDto;
use App\ApiDto\Previsionnel\PrevisionnelEnseignementDto;
use App\ApiDto\Previsionnel\PrevisionnelPersonnelDto;
use App\ApiDto\Previsionnel\PrevisionnelSemestreDto;
use App\Entity\Edt\EdtProgression;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Personnel;
use App\Filter\PrevisionnelFilter;
use App\Repository\Previsionnel\PrevisionnelRepository;
use App\State\Previsionnel\PrevisionnelAllPersonnelsProvider;
use App\State\Previsionnel\PrevisionnelEnseignementProvider;
use App\State\Previsionnel\PrevisionnelPersonnelProvider;
use App\State\Previsionnel\PrevisionnelSemestreProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PrevisionnelRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['previsionnel:read']]),
        new GetCollection(
            normalizationContext: ['groups' => ['previsionnel:read']]
        ),
        new GetCollection(
            uriTemplate: '/previsionnels_semestre',
            normalizationContext: ['groups' => ['previsionnel_semestre:read']],
            output: PrevisionnelSemestreDto::class,
            provider: PrevisionnelSemestreProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/previsionnels_enseignement',
            normalizationContext: ['groups' => ['previsionnel_enseignement:read']],
            output: PrevisionnelEnseignementDto::class,
            provider: PrevisionnelEnseignementProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/previsionnels_all_personnels',
            normalizationContext: ['groups' => ['previsionnel_all_personnels:read']],
            output: PrevisionnelAllPersonnelsDto::class,
            provider: PrevisionnelAllPersonnelsProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/previsionnels_personnel',
            normalizationContext: ['groups' => ['previsionnel_personnel:read']],
            output: PrevisionnelPersonnelDto::class,
            provider: PrevisionnelPersonnelProvider::class,
        ),
        new Patch(normalizationContext: ['groups' => ['previsionnel:read']]),
    ],
    paginationEnabled: false,
)]
#[ApiFilter(PrevisionnelFilter::class)]
class Previsionnel
{
    public const DUREE_SEANCE = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'previsionnels')]
    #[Groups(['previsionnel:read', 'scol_enseignement:read', 'previsionnel_semestre:read', 'previsionnel_enseignement:read', 'previsionnel_personnel:read', 'previsionnel_all_personnels:read'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'previsionnels')]
    #[Groups(['previsionnel:read'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?bool $referent = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['previsionnel:read', 'scol_enseignement:read', 'previsionnel_semestre:read','previsionnel_all_personnels:read', 'previsionnel_personnel:read'])]
    private array $heures = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['previsionnel:read', 'scol_enseignement:read', 'previsionnel_semestre:read'])]
    private ?array $groupes = [];

    #[ORM\ManyToOne(inversedBy: 'previsionnels')]
    #[Groups(['previsionnel:read', 'previsionnel_semestre:read', 'previsionnel_all_personnels:read', 'previsionnel_personnel:read'])]
    private ?ScolEnseignement $enseignement = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[Groups(['previsionnel:read'])]
    private ?EdtProgression $progression = null;

    public function __construct()
    {

    }

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

    public function getNbSeanceCm(): ?float
    {
        return $this->heures['CM'] / self::DUREE_SEANCE;
    }

    public function getNbSeanceTd(): ?float
    {
        return $this->heures['TD'] / self::DUREE_SEANCE;
    }

    public function getNbSeanceTp(): ?float
    {
        return $this->heures['TP'] / self::DUREE_SEANCE;
    }

    public function getHeures(): array
    {
        return $this->heures;
    }

    public function setHeures(array $heures): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptionsHeures($resolver);

        // Convertir toutes les valeurs en float
        $heures = array_map(function($value) {
            return (float) $value;
        }, $heures);

        $this->heures = $resolver->resolve($heures);

        return $this;
    }

    public function configureOptionsHeures(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'CM' => 0.0,
            'TD' => 0.0,
            'TP' => 0.0,
            'Projet' => 0.0,
        ]);

        $resolver->setAllowedTypes('CM', 'float');
        $resolver->setAllowedTypes('TD', 'float');
        $resolver->setAllowedTypes('TP', 'float');
        $resolver->setAllowedTypes('Projet', 'float');
    }

    public function getGroupes(): array
    {
        return $this->groupes;
    }

    public function setGroupes(?array $groupes): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptionsGroupes($resolver);
        $this->groupes = $resolver->resolve($groupes);

        return $this;
    }

    public function configureOptionsGroupes(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'CM' =>  0,
            'TD' => 0,
            'TP' => 0,
            'Projet' => 0,
        ]);

        $resolver->setAllowedTypes('CM', 'int');
        $resolver->setAllowedTypes('TD', 'int');
        $resolver->setAllowedTypes('TP', 'int');
        $resolver->setAllowedTypes('Projet', 'int');
    }
}
