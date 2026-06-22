<?php

namespace StageBundle\Entity\Stages;

use App\Entity\Traits\LifeCycleTrait;
use App\ValueObject\Adresse;
use StageBundle\Repository\Stages\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Entreprise
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(groups: ['stage_entreprise_administration', 'stage_periode_gestion', 'stage_etudiant:read'])]
    private ?int $id = null;

    #[Assert\Length(min: 0, max: 30, maxMessage: 'Maximum {{ limit }} caractères')]
    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[Groups(groups: ['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $siret = null;

    #[Groups(groups: ['stage_entreprise_administration', 'alternance_administration', 'stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $raisonSociale = null;

    #[Groups(groups: ['stage_entreprise_administration', 'alternance_administration', 'stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adresse = null;

    #[Groups(groups: ['stage_entreprise_administration', 'alternance_administration', 'stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\OneToOne(targetEntity: Contact::class, cascade: ['persist', 'remove'])]
    private ?Contact $responsable = null;

    /**
     * @var Collection<int, StageEtudiant>
     */
    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: StageEtudiant::class)]
    private Collection $stageEtudiants;

    public function __construct()
    {
        $this->stageEtudiants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getRaisonSociale(): ?string
    {
        return $this->raisonSociale;
    }

    public function setRaisonSociale(string $raisonSociale): self
    {
        $this->raisonSociale = $raisonSociale;

        return $this;
    }

    public function getAdresse(): ?Adresse
    {
        if ($this->adresse === null) {
            return null;
        }
        return Adresse::fromArray($this->adresse);
    }

    public function setAdresse(?Adresse $adresse): self
    {
        $this->adresse = $adresse ? $adresse->toArray() : null;

        return $this;
    }

    public function getResponsable(): ?Contact
    {
        return $this->responsable;
    }

    public function setResponsable(?Contact $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * @return Collection<int, StageEtudiant>
     */
    public function getStageEtudiants(): Collection
    {
        return $this->stageEtudiants;
    }

    public function addStageEtudiant(StageEtudiant $stageEtudiant): self
    {
        if (!$this->stageEtudiants->contains($stageEtudiant)) {
            $this->stageEtudiants->add($stageEtudiant);
            $stageEtudiant->setEntreprise($this);
        }

        return $this;
    }

    public function removeStageEtudiant(StageEtudiant $stageEtudiant): self
    {
        if ($this->stageEtudiants->removeElement($stageEtudiant)) {
            if ($stageEtudiant->getEntreprise() === $this) {
                $stageEtudiant->setEntreprise(null);
            }
        }

        return $this;
    }

    public function getArray(): array
    {
        return [
            'siret' => $this->getSiret(),
            'raisonSociale' => $this->getRaisonSociale(),
            'adresse' => $this->getAdresse()?->toArray() ?? [],
            'responsable' => $this->getResponsable()?->getDisplay() ?? '',
        ];
    }
}
