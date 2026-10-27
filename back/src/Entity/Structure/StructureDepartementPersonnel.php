<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\Users\Personnel;
use App\Entity\Traits\OldIdTrait;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use App\Filter\DepartementPersonnelFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: StructureDepartementPersonnelRepository::class)]
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
        new Post(
            uriTemplate: '/structure_departement_personnels/{id}/change_departement',
            inputFormats: ['json' => ['application/ld+json']],
            outputFormats: ['json' => ['application/ld+json']],
            normalizationContext: ['groups' => ['departement_personnel:read']],
            validationContext: ['groups' => ['change_departement']],
            processor: 'App\State\ChangeDepartementProcessor'
        ),
        new Post(
            security: "is_granted('SUPER_ADMIN')",
            normalizationContext: ['groups' => ['departement_personnel:read']],
            denormalizationContext: ['groups' => ['departement_personnel:write']]
        ),
        new Patch(
            security: "is_granted('SUPER_ADMIN')",
            normalizationContext: ['groups' => ['departement_personnel:read']],
            denormalizationContext: ['groups' => ['departement_personnel:write']]
        ),
        new Delete(security: "is_granted('SUPER_ADMIN')")
    ],
    order: ['personnel.nom', 'departement.libelle'],
    parameters: [
        'departement' => new QueryParameter(filter: DepartementPersonnelFilter::class),
        'personnel' => new QueryParameter(filter: DepartementPersonnelFilter::class),
        'personnel.nom' => new QueryParameter(filter: SearchFilter::class),
        'personnel.prenom' => new QueryParameter(filter: SearchFilter::class),
        'departement.libelle' => new QueryParameter(filter: SearchFilter::class),
    ]
)]
class StructureDepartementPersonnel
{
    use OldIdTrait; // référence V3 temporaire pour contrôler la bascule
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(groups: ['personnel:read', 'departement_personnel:read', 'departement:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(groups: ['departement_personnel:read', 'departement_personnel:write', 'departement:read'])]
    private array $packages = ["intranet"];

    #[ORM\Column]
    #[Groups(groups: ['departement_personnel:read', 'departement_personnel:write', 'departement:read'])]
    private array $permissions = [];

    /** @var Collection<int, StructureDiplome> */
    #[ORM\ManyToMany(targetEntity: StructureDiplome::class)]
    #[ORM\JoinTable(name: 'departement_personnel_parcours_diplome')]
    #[ORM\JoinColumn(name: 'affectation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'diplome_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups(['personnel:read', 'departement_personnel:read', 'departement_personnel:write', 'departement:read'])]
    private Collection $responsableParcoursDiplomes;

    #[ORM\Column]
    #[Groups(groups: ['personnel:read', 'departement_personnel:read', 'departement:read', 'departement_personnel:write'])]
    private ?bool $defaut = null;

    #[ORM\ManyToOne(inversedBy: 'departementsPersonnel')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(groups: ['departement_personnel:read', 'departement:read', 'departement_personnel:write'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'departementPersonnels')]
    #[Groups(groups: ['personnel:read', 'departement_personnel:read', 'departement_personnel:write'])]
    private ?StructureDepartement $departement = null;

    #[ORM\Column]
    #[Groups(groups: ['departement_personnel:read', 'departement_personnel:write'])]
    private bool $affectation = false;

    public function __construct()
    {
        $this->responsableParcoursDiplomes = new ArrayCollection();
    }

    /** @return Collection<int, StructureDiplome> */
    public function getResponsableParcoursDiplomes(): Collection
    {
        return $this->responsableParcoursDiplomes;
    }

    public function addResponsableParcoursDiplome(StructureDiplome $diplome): static
    {
        if (!$this->responsableParcoursDiplomes->contains($diplome)) {
            $this->responsableParcoursDiplomes->add($diplome);
        }
        return $this;
    }

    public function removeResponsableParcoursDiplome(StructureDiplome $diplome): static
    {
        $this->responsableParcoursDiplomes->removeElement($diplome);
        return $this;
    }

    public function hasValidResponsableParcoursDiplomes(): bool
    {
        $departmentId = $this->departement?->getId();
        if ($departmentId === null || $this->responsableParcoursDiplomes->isEmpty()) return false;
        foreach ($this->responsableParcoursDiplomes as $diplome) {
            if ($diplome->getId() === null || $diplome->getDepartement()?->getId() !== $departmentId) return false;
        }
        return true;
    }

    public function isResponsableParcoursForDiplome(StructureDiplome $diplome): bool
    {
        if ($diplome->getId() === null || !$this->hasValidResponsableParcoursDiplomes()) return false;
        foreach ($this->responsableParcoursDiplomes as $assignedDiplome) {
            if ($assignedDiplome->getId() === $diplome->getId()
                && $diplome->getDepartement()?->getId() === $this->departement?->getId()) return true;
        }
        return false;
    }

    #[Assert\Callback]
    public function validateResponsableParcours(ExecutionContextInterface $context): void
    {
        $hasRole = in_array('ROLE_RESP_PARCOURS', $this->getPermissions(), true);
        if ($hasRole && !$this->hasValidResponsableParcoursDiplomes()) {
            $context->buildViolation('Le responsable de parcours doit être rattaché à au moins un diplôme, tous appartenant à ce département.')
                ->atPath('responsableParcoursDiplomes')->addViolation();
        }
        if (!$hasRole && !$this->responsableParcoursDiplomes->isEmpty()) {
            $context->buildViolation('Le rattachement à des diplômes nécessite le rôle Responsable de parcours.')
                ->atPath('responsableParcoursDiplomes')->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPackages(): array
    {
        return $this->packages ?? [];
    }

    public function setPackages(array $packages): static
    {
        $this->packages = $packages;

        return $this;
    }

    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    public function setPermissions(array $permissions): static
    {
        $this->permissions = $permissions;

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
