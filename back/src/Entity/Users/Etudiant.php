<?php

namespace App\Entity\Users;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Entity\Etudiant\EtudiantAbsence;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Filter\EtudiantFilter;
use App\Repository\EtudiantRepository;
use App\ValueObject\Adresse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Entity(repositoryClass: EtudiantRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['etudiant:read']]),
        new GetCollection(normalizationContext: ['groups' => ['etudiant:read']]),
        new Patch(normalizationContext: ['groups' => ['etudiant:write']]),
    ]
)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(EtudiantFilter::class)]
#[ApiFilter(SearchFilter::class, properties: [
    'nom' => 'start',
    'prenom' => 'start',
    'mailUniv' => 'partial'
])]
class Etudiant implements UserInterface, PasswordAuthenticatedUserInterface
{
    use LifeCycleTrait;
    use EduSignTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $username;

    #[ORM\Column(length: 255)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $mailUniv;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read', 'etudiant:write'])]
    private ?string $mailPerso;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['etudiant:read'])]
    private array $roles = [];

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $prenom;

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $nom;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?string $photoName = null;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'etudiant', orphanRemoval: true)]
    #[Groups(['etudiant:read'])]
    #[MaxDepth(1)]
    private Collection $scolarites;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read', 'etudiant:write'])]
    private ?array $adresseEtudiante = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read', 'etudiant:write'])]
    private ?array $adresseParentale = null;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'etudiant')]
    private Collection $absences;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['scolarite:read', 'etudiant:read', 'etudiant:write'])]
    private ?string $site_perso = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etudiant:read'])]
    private ?string $site_univ = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?string $num_etudiant = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?string $num_ine = null;

    #[ORM\Column(nullable: true)]
    private ?int $annee_bac = null;

    #[ORM\Column]
    private ?bool $boursier = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $amenagements_particuliers = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?int $promotion = null;

    #[ORM\Column(nullable: true)]
    private ?int $annee_sortie = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['scolarite:read'])]
    private ?\DateTimeInterface $date_naissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?string $tel1 = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['etudiant:read'])]
    private ?string $tel2 = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $lieu_naissance = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['etudiant:read'])]
    private ?array $applications = null;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, inversedBy: 'etudiants')]
    private Collection $groupes;

    public function __construct()
    {
        $this->scolarites = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->groupes = new ArrayCollection();
    }

    public function getMails(): array
    {
        return [$this->mailUniv];
    }

    public function getTypeUser(): ?string
    {
        return 'etudiant';
    }

    public function __toString(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getMailUniv(): ?string
    {
        return $this->mailUniv;
    }

    public function setMailUniv(string $mailUniv): static
    {
        $this->mailUniv = $mailUniv;

        return $this;
    }

    public function getMailPerso(): string
    {
        return $this->mailPerso;
    }

    public function setMailPerso(?string $mailPerso): void
    {
        $this->mailPerso = $mailPerso;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPhotoName(): ?string
    {
        return $this->photoName;
    }

    public function setPhotoName(?string $photoName): static
    {
        $this->photoName = $photoName;

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
            $scolarite->setEtudiant($this);
        }

        return $this;
    }

    public function removeScolarite(EtudiantScolarite $scolarite): static
    {
        if ($this->scolarites->removeElement($scolarite)) {
            // set the owning side to null (unless already changed)
            if ($scolarite->getEtudiant() === $this) {
                $scolarite->setEtudiant(null);
            }
        }

        return $this;
    }

    public function setAdresseEtudiante(Adresse $adresse): void
    {
        $this->adresseEtudiante = $adresse->toArray();
    }

    public function getAdresseEtudiante(): ?Adresse
    {
        if ($this->adresseEtudiante === null) {
            return null;
        }

        $data = [
            'adresse' => $this->adresseEtudiante['adresse'],
            'complement1' => $this->adresseEtudiante['complement1'],
            'complement2' => $this->adresseEtudiante['complement2'],
            'ville' => $this->adresseEtudiante['ville'],
            'codePostal' => $this->adresseEtudiante['codePostal'],
            'pays' => $this->adresseEtudiante['pays'],
        ];

        return Adresse::fromArray($data);
    }

    public function setAdresseParentale(Adresse $adresse): void
    {
        $this->adresseParentale= $adresse->toArray();
    }

    public function getAdresseParentale(): ?Adresse
    {
        if ($this->adresseParentale === null) {
            return null;
        }

        $data = [
            'adresse' => $this->adresseParentale['adresse'],
            'complement1' => $this->adresseParentale['complement1'],
            'complement2' => $this->adresseParentale['complement2'],
            'ville' => $this->adresseParentale['ville'],
            'codePostal' => $this->adresseParentale['codePostal'],
            'pays' => $this->adresseParentale['pays'],
        ];

        return Adresse::fromArray($data);
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(EtudiantAbsence $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setEtudiant($this);
        }

        return $this;
    }

    public function removeAbsence(EtudiantAbsence $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getEtudiant() === $this) {
                $absence->setEtudiant(null);
            }
        }

        return $this;
    }

    public function getApplications(): ?array
    {
        return $this->applications ?? ['UniTranet'];
    }

    public function setApplications(?array $applications): static
    {
        $this->applications = $applications;

        return $this;
    }

    public function getSitePerso(): ?string
    {
        return $this->site_perso;
    }

    public function setSitePerso(?string $site_perso): static
    {
        $this->site_perso = $site_perso;

        return $this;
    }

    public function getSiteUniv(): ?string
    {
        return $this->site_univ;
    }

    public function setSiteUniv(?string $site_univ): static
    {
        $this->site_univ = $site_univ;

        return $this;
    }

    public function getNumEtudiant(): ?string
    {
        return $this->num_etudiant;
    }

    public function setNumEtudiant(?string $num_etudiant): static
    {
        $this->num_etudiant = $num_etudiant;

        return $this;
    }

    public function getNumIne(): ?string
    {
        return $this->num_ine;
    }

    public function setNumIne(?string $num_ine): static
    {
        $this->num_ine = $num_ine;

        return $this;
    }

    public function getAnneeBac(): ?int
    {
        return $this->annee_bac;
    }

    public function setAnneeBac(?int $annee_bac): static
    {
        $this->annee_bac = $annee_bac;

        return $this;
    }

    public function isBoursier(): ?bool
    {
        return $this->boursier;
    }

    public function setBoursier(bool $boursier): static
    {
        $this->boursier = $boursier;

        return $this;
    }

    public function getAmenagementsParticuliers(): ?string
    {
        return $this->amenagements_particuliers;
    }

    public function setAmenagementsParticuliers(?string $amenagements_particuliers): static
    {
        $this->amenagements_particuliers = $amenagements_particuliers;

        return $this;
    }

    public function getPromotion(): ?int
    {
        return $this->promotion;
    }

    public function setPromotion(?int $promotion): static
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getAnneeSortie(): ?int
    {
        return $this->annee_sortie;
    }

    public function setAnneeSortie(?int $annee_sortie): static
    {
        $this->annee_sortie = $annee_sortie;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?\DateTimeInterface $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getTel1(): ?string
    {
        return $this->tel1;
    }

    public function setTel1(?string $tel1): static
    {
        $this->tel1 = $tel1;

        return $this;
    }

    public function getTel2(): ?string
    {
        return $this->tel2;
    }

    public function setTel2(?string $tel2): static
    {
        $this->tel2 = $tel2;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieu_naissance;
    }

    public function setLieuNaissance(?string $lieu_naissance): static
    {
        $this->lieu_naissance = $lieu_naissance;

        return $this;
    }

    /**
     * @return Collection<int, StructureGroupe>
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(StructureGroupe $groupe): static
    {
        if (!$this->groupes->contains($groupe)) {
            $this->groupes->add($groupe);
        }

        return $this;
    }

    public function removeGroupe(StructureGroupe $groupe): static
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }
}
