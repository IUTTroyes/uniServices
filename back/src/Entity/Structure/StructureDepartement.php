<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Traits\OptionTrait;
use App\Entity\Traits\UuidTrait;
use App\Filter\DepartementFilter;
use App\Repository\Structure\StructureDepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureDepartementRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['departement:read']]),
        new Get(normalizationContext: ['groups' => ['departement:read']]),
    ]
)]
#[ApiFilter(DepartementFilter::class)]
#[ORM\HasLifecycleCallbacks]
class StructureDepartement
{
    use UuidTrait;
    use LifeCycleTrait;
    use OptionTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(groups: ['departement:read', 'personnel:read', 'departement_personnel:read', 'scoralite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(groups: ['departement:read', 'personnel:read', 'etudiant:read', 'departement_personnel:read', 'scolarite:read'])]
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
    #[Groups(groups: ['departement:read', 'personnel:read'])]
    private ?bool $actif = null;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'departement')]
    private Collection $diplomes;

    /**
     * @var Collection<int, StructureDepartementPersonnel>
     */
    #[ORM\OneToMany(targetEntity: StructureDepartementPersonnel::class, mappedBy: 'departement')]
    #[Groups(groups: ['departement:read'])]
    private Collection $departementPersonnels;

    /**
     * @var Collection<int, ApcReferentiel>
     */
    #[ORM\OneToMany(targetEntity: ApcReferentiel::class, mappedBy: 'departement')]
    private Collection $referentiels;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'departement')]
    private Collection $scolarites;

    public function __construct()
    {
        $this->diplomes = new ArrayCollection();
        $this->departementPersonnels = new ArrayCollection();
        $this->setOpt([]);
        $this->referentiels = new ArrayCollection();
        $this->scolarites = new ArrayCollection();
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
        return $this->diplomes;
    }

    public function addStructureDiplome(StructureDiplome $structureDiplome): static
    {
        if (!$this->diplomes->contains($structureDiplome)) {
            $this->diplomes->add($structureDiplome);
            $structureDiplome->setDepartement($this);
        }

        return $this;
    }

    public function removeStructureDiplome(StructureDiplome $structureDiplome): static
    {
        if ($this->diplomes->removeElement($structureDiplome)) {
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
    public function getDepartementPersonnels(): Collection
    {
        return $this->departementPersonnels;
    }

    public function addDepartementPersonnel(StructureDepartementPersonnel $departementPersonnel): static
    {
        if (!$this->departementPersonnels->contains($departementPersonnel)) {
            $this->departementPersonnels->add($departementPersonnel);
            $departementPersonnel->setDepartement($this);
        }

        return $this;
    }

    public function removeDepartementPersonnel(StructureDepartementPersonnel $departementPersonnel): static
    {
        if ($this->departementPersonnels->removeElement($departementPersonnel)) {
            // set the owning side to null (unless already changed)
            if ($departementPersonnel->getDepartement() === $this) {
                $departementPersonnel->setDepartement(null);
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
    public function getReferentiels(): Collection
    {
        return $this->referentiels;
    }

    public function addReferentiel(ApcReferentiel $referentiel): static
    {
        if (!$this->referentiels->contains($referentiel)) {
            $this->referentiels->add($referentiel);
            $referentiel->setDepartement($this);
        }

        return $this;
    }

    public function removeReferentiel(ApcReferentiel $referentiel): static
    {
        if ($this->referentiels->removeElement($referentiel)) {
            // set the owning side to null (unless already changed)
            if ($referentiel->getDepartement() === $this) {
                $referentiel->setDepartement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtudiantScolarite>
     */
    public function getScolarites(): Collection
    {
        return $this->scolarites;
    }

    public function addScolarite(EtudiantScolarite $scolarite): static
    {
        if (!$this->scolarites->contains($scolarite)) {
            $this->scolarites->add($scolarite);
            $scolarite->setDepartement($this);
        }

        return $this;
    }

    public function removeScolarite(EtudiantScolarite $scolarite): static
    {
        if ($this->scolarites->removeElement($scolarite)) {
            // set the owning side to null (unless already changed)
            if ($scolarite->getDepartement() === $this) {
                $scolarite->setDepartement(null);
            }
        }

        return $this;
    }
}
