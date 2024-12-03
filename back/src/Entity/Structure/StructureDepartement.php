<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Apc\ApcReferentiel;
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
    private ?string $logoName = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $telContact = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteWeb = null;

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

    /**
     * @var Collection<int, ApcReferentiel>
     */
    #[ORM\OneToMany(targetEntity: ApcReferentiel::class, mappedBy: 'departement')]
    private Collection $apcReferentiels;

    public function __construct()
    {
        $this->structureDiplomes = new ArrayCollection();
        $this->structureDepartementPersonnels = new ArrayCollection();
        $this->setOpt([]);
        $this->apcReferentiels = new ArrayCollection();
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
        return $this->logoName;
    }

    public function setLogoName(?string $logoName): static
    {
        $this->logoName = $logoName;

        return $this;
    }

    public function getTelContact(): ?string
    {
        return $this->telContact;
    }

    public function setTelContact(?string $telContact): static
    {
        $this->telContact = $telContact;

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
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;

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

    /**
     * @return Collection<int, ApcReferentiel>
     */
    public function getApcReferentiels(): Collection
    {
        return $this->apcReferentiels;
    }

    public function addApcReferentiel(ApcReferentiel $apcReferentiel): static
    {
        if (!$this->apcReferentiels->contains($apcReferentiel)) {
            $this->apcReferentiels->add($apcReferentiel);
            $apcReferentiel->setDepartement($this);
        }

        return $this;
    }

    public function removeApcReferentiel(ApcReferentiel $apcReferentiel): static
    {
        if ($this->apcReferentiels->removeElement($apcReferentiel)) {
            // set the owning side to null (unless already changed)
            if ($apcReferentiel->getDepartement() === $this) {
                $apcReferentiel->setDepartement(null);
            }
        }

        return $this;
    }
}
