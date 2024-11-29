<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OptionTrait;
use App\Entity\Traits\UuidTrait;
use App\Repository\StructureDepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureDepartementRepository::class)]
#[ApiResource]
#[ORM\HasLifecycleCallbacks]
class StructureDepartement
{
    use UuidTrait;
    use LifeCycleTrait;
    use OptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(groups: ['structure_departement:read', 'personnel:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(groups: ['structure_departement:read', 'personnel:read'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo_name = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $tel_contact = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $site_web = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(groups: ['structure_departement:read', 'personnel:read'])]
    private ?bool $actif = null;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'departement')]
    private Collection $structureDiplomes;

    /**
     * @var Collection<int, StructureDepartementPersonnel>
     */
    #[ORM\OneToMany(targetEntity: StructureDepartementPersonnel::class, mappedBy: 'departement')]
    private Collection $structureDepartementPersonnels;

    public function __construct()
    {
        $this->structureDiplomes = new ArrayCollection();
        $this->structureDepartementPersonnels = new ArrayCollection();
        $this->setOpt([]);
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

    public function getLogoName(): ?string
    {
        return $this->logo_name;
    }

    public function setLogoName(?string $logo_name): static
    {
        $this->logo_name = $logo_name;

        return $this;
    }

    public function getTelContact(): ?string
    {
        return $this->tel_contact;
    }

    public function setTelContact(?string $tel_contact): static
    {
        $this->tel_contact = $tel_contact;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->site_web;
    }

    public function setSiteWeb(?string $site_web): static
    {
        $this->site_web = $site_web;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, StructureDiplome>
     */
    public function getStructureDiplomes(): Collection
    {
        return $this->structureDiplomes;
    }

    public function addStructureDiplome(StructureDiplome $structureDiplome): static
    {
        if (!$this->structureDiplomes->contains($structureDiplome)) {
            $this->structureDiplomes->add($structureDiplome);
            $structureDiplome->setDepartement($this);
        }

        return $this;
    }

    public function removeStructureDiplome(StructureDiplome $structureDiplome): static
    {
        if ($this->structureDiplomes->removeElement($structureDiplome)) {
            // set the owning side to null (unless already changed)
            if ($structureDiplome->getDepartement() === $this) {
                $structureDiplome->setDepartement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructureDepartementPersonnel>
     */
    public function getStructureDepartementPersonnels(): Collection
    {
        return $this->structureDepartementPersonnels;
    }

    public function addStructureDepartementPersonnel(StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        if (!$this->structureDepartementPersonnels->contains($structureDepartementPersonnel)) {
            $this->structureDepartementPersonnels->add($structureDepartementPersonnel);
            $structureDepartementPersonnel->setDepartement($this);
        }

        return $this;
    }

    public function removeStructureDepartementPersonnel(StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        if ($this->structureDepartementPersonnels->removeElement($structureDepartementPersonnel)) {
            // set the owning side to null (unless already changed)
            if ($structureDepartementPersonnel->getDepartement() === $this) {
                $structureDepartementPersonnel->setDepartement(null);
            }
        }

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'materiel' => false,
            'edt' => false,
            'stage' => false,
            'resp_ri' => '',
        ]);

        $resolver->setAllowedTypes('materiel', 'bool');
        $resolver->setAllowedTypes('edt', 'bool');
        $resolver->setAllowedTypes('stage', 'bool');
        $resolver->setAllowedTypes('resp_ri', 'string'); //todo: sauvegarder l'IRI de la personne ? pour faire le lien en front ?
    }
}
