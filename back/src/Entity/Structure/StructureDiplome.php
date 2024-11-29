<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OptionTrait;
use App\Entity\Users\Personnel;
use App\Repository\StructureDiplomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureDiplomeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['structure_diplome:read']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_diplome:read']]),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class StructureDiplome
{
    use EduSignTrait;
    use LifeCycleTrait;
    use OptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['structure_diplome:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'responsableDiplome')]
    private ?Personnel $responsable_diplome = null;

    #[ORM\ManyToOne(inversedBy: 'assistant_diplome')]
    private ?Personnel $assistant_diplome = null;

    #[ORM\Column]
    private ?int $volume_horaire = null;

    #[ORM\Column]
    private ?int $code_celcat_departement = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $sigle = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    private ?Collection $enfants = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo_partenaire = null;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'diplome')]
    private Collection $structurePns;

    #[ORM\ManyToOne(inversedBy: 'structureDiplomes')]
    private ?StructureDepartement $departement = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $apogeeCodeVersion = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $apogeeCodeDiplome = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $apogeeCodeDepartement = null;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
        $this->structurePns = new ArrayCollection();
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

    public function getResponsableDiplome(): ?Personnel
    {
        return $this->responsable_diplome;
    }

    public function setResponsableDiplome(?Personnel $responsable_diplome): static
    {
        $this->responsable_diplome = $responsable_diplome;

        return $this;
    }

    public function getAssistantDiplome(): ?Personnel
    {
        return $this->assistant_diplome;
    }

    public function setAssistantDiplome(?Personnel $assistant_diplome): static
    {
        $this->assistant_diplome = $assistant_diplome;

        return $this;
    }

    public function getVolumeHoraire(): ?int
    {
        return $this->volume_horaire;
    }

    public function setVolumeHoraire(int $volume_horaire): static
    {
        $this->volume_horaire = $volume_horaire;

        return $this;
    }

    public function getCodeCelcatDepartement(): ?int
    {
        return $this->code_celcat_departement;
    }

    public function setCodeCelcatDepartement(int $code_celcat_departement): static
    {
        $this->code_celcat_departement = $code_celcat_departement;

        return $this;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): static
    {
        $this->sigle = $sigle;

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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(?self $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }

        return $this;
    }

    public function getLogoPartenaire(): ?string
    {
        return $this->logo_partenaire;
    }

    public function setLogoPartenaire(?string $logo_partenaire): static
    {
        $this->logo_partenaire = $logo_partenaire;

        return $this;
    }

    /**
     * @return Collection<int, StructurePn>
     */
    public function getStructurePns(): Collection
    {
        return $this->structurePns;
    }

    public function addStructurePn(StructurePn $structurePn): static
    {
        if (!$this->structurePns->contains($structurePn)) {
            $this->structurePns->add($structurePn);
            $structurePn->setDiplome($this);
        }

        return $this;
    }

    public function removeStructurePn(StructurePn $structurePn): static
    {
        if ($this->structurePns->removeElement($structurePn)) {
            // set the owning side to null (unless already changed)
            if ($structurePn->getDiplome() === $this) {
                $structurePn->setDiplome(null);
            }
        }

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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'nb_jours_saisie_absence' => 15,
            'supp_absence' => true,
            'anonymat' => false,
            'commentaire_releve' => false,
            'espace_perso_visible' => false,
            'semaine_visible' => 2,
            'certif_qualite' => true,
            'resp_qualite' => 0,
            'update_celcat' => false,
            'saisie_cm_autorisee' => true,
        ]);

        $resolver->setAllowedTypes('nb_jours_saisie_absence', 'int');
        $resolver->setAllowedTypes('supp_absence', 'bool');
        $resolver->setAllowedTypes('anonymat', 'bool');
        $resolver->setAllowedTypes('commentaire_releve', 'bool');
        $resolver->setAllowedTypes('espace_perso_visible', 'bool');
        $resolver->setAllowedTypes('semaine_visible', 'int');
        $resolver->setAllowedTypes('certif_qualite', 'bool');
        $resolver->setAllowedTypes('resp_qualite', 'int');
        $resolver->setAllowedTypes('update_celcat', 'bool');
        $resolver->setAllowedTypes('saisie_cm_autorisee', 'bool');
    }

    public function getApogeeCodeVersion(): ?string
    {
        return $this->apogeeCodeVersion;
    }

    public function setApogeeCodeVersion(?string $apogeeCodeVersion): static
    {
        $this->apogeeCodeVersion = $apogeeCodeVersion;

        return $this;
    }

    public function getApogeeCodeDiplome(): ?string
    {
        return $this->apogeeCodeDiplome;
    }

    public function setApogeeCodeDiplome(?string $apogeeCodeDiplome): static
    {
        $this->apogeeCodeDiplome = $apogeeCodeDiplome;

        return $this;
    }

    public function getApogeeCodeDepartement(): ?string
    {
        return $this->apogeeCodeDepartement;
    }

    public function setApogeeCodeDepartement(?string $apogeeCodeDepartement): static
    {
        $this->apogeeCodeDepartement = $apogeeCodeDepartement;

        return $this;
    }
}
