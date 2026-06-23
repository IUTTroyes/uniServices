<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\ApiDto\Users\ActionsUrgentesWidgetDto;
use App\Entity\Users\Personnel;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use App\Filter\DepartementPersonnelFilter;
use App\State\Provider\Users\ActionsUrgentesWidgetProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureDepartementPersonnelRepository::class)]
#[ApiFilter(DepartementPersonnelFilter::class, SearchFilter::class, properties: [
    'personnel.nom' => 'partial',
    'personnel.prenom' => 'partial',
    'departement.libelle' => 'partial'
])]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['departement_personnel:read']]),
        new GetCollection(normalizationContext: ['groups' => ['departement_personnel:read']]),
        new GetCollection(
            uriTemplate: '/structure_departement_personnels/by_departement/{departementId}',
            uriVariables: [
                'departementId' => new Link(fromClass: StructureDepartement::class, identifiers: ['id'], toProperty: 'departement')
            ],
            paginationEnabled: false,
            normalizationContext: ['groups' => ['departement_personnel:read']]
        ),
        new GetCollection(
            uriTemplate: '/widget/actions_urgentes/',
            normalizationContext: ['groups' => ['action_urgente_widget:read']],
            output: ActionsUrgentesWidgetDto::class,
            provider: ActionsUrgentesWidgetProvider::class,
        ),
        new Post(
            uriTemplate: '/structure_departement_personnels/{id}/change_departement',
            inputFormats: ['json' => ['application/ld+json']],
            outputFormats: ['json' => ['application/ld+json']],
            normalizationContext: ['groups' => ['departement_personnel:read']],
            processor: 'App\State\ChangeDepartementProcessor'),
        new Post(
            normalizationContext: ['groups' => ['departement_personnel:read']],
            denormalizationContext: ['groups' => ['departement_personnel:write']]
        ),
        new Patch(
            normalizationContext: ['groups' => ['departement_personnel:read']],
            denormalizationContext: ['groups' => ['departement_personnel:write']]
        ),
        new Delete()
    ],
    order: ['personnel.nom', 'departement.libelle']
)]
class StructureDepartementPersonnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(groups: ['personnel:read', 'departement_personnel:read', 'departement:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(groups: ['departement_personnel:read', 'departement_personnel:write'])]
    private array $roles = []; //tableau associatif => 'application' => [droits]

    #[ORM\Column]
    #[Groups(groups: ['personnel:read', 'departement_personnel:read', 'departement:read', 'departement_personnel:write'])]
    private ?bool $defaut = null;

    #[ORM\ManyToOne(inversedBy: 'departementPersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(groups: ['departement_personnel:read', 'departement:read', 'departement_personnel:write'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'departementPersonnels')]
    #[Groups(groups: ['personnel:read', 'departement_personnel:read', 'departement_personnel:write'])]
    private ?StructureDepartement $departement = null;

    #[ORM\Column]
    #[Groups(groups: ['departement_personnel:read', 'departement_personnel:write'])]
    private bool $affectation = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function isDefaut(): ?bool
    {
        return $this->defaut;
    }

    public function setDefaut(bool $defaut): static
    {
        $this->defaut = $defaut;

        return $this;
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

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function isAffectation(): bool
    {
        return $this->affectation;
    }

    public function setAffectation(bool $affectation): static
    {
        $this->affectation = $affectation;

        return $this;
    }
}
