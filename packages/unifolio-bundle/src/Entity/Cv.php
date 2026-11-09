<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Users\Etudiant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Repository\CvRepository;

/** Curriculum vitæ de l'étudiant : source des blocs « CV » des portfolios. Un seul CV par étudiant. */
#[ORM\Entity(repositoryClass: CvRepository::class)]
#[ORM\Table(name: 'unifolio_cv')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_cv_etudiant', columns: ['etudiant_id'])]
class Cv implements TimestampableInterface
{
    use TimestampableTrait;

    public const ELEMENTS_MAX = 30;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Etudiant $etudiant;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $poste = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var list<array{langue: string, niveau: string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $langues = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $softSkills = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $hardSkills = [];

    /** @var list<array{type: string, url: string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $reseaux = [];

    /** @var Collection<int, CvExperience> */
    #[ORM\OneToMany(targetEntity: CvExperience::class, mappedBy: 'cv', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $experiences;

    /** @var Collection<int, CvFormation> */
    #[ORM\OneToMany(targetEntity: CvFormation::class, mappedBy: 'cv', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $formations;

    public function __construct(Etudiant $etudiant)
    {
        $this->etudiant = $etudiant;
        $this->experiences = new ArrayCollection();
        $this->formations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiant(): Etudiant
    {
        return $this->etudiant;
    }

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(?string $poste): static
    {
        $this->poste = $poste !== null && trim($poste) !== '' ? mb_substr(trim($poste), 0, 255) : null;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description !== null && trim($description) !== '' ? mb_substr(trim($description), 0, 3000) : null;

        return $this;
    }

    /** @return list<array{langue: string, niveau: string}> */
    public function getLangues(): array
    {
        return $this->langues;
    }

    /** @param list<array{langue: string, niveau: string}> $langues */
    public function setLangues(array $langues): static
    {
        $this->langues = \array_slice($langues, 0, 15);

        return $this;
    }

    /** @return list<string> */
    public function getSoftSkills(): array
    {
        return $this->softSkills;
    }

    /** @param list<string> $skills */
    public function setSoftSkills(array $skills): static
    {
        $this->softSkills = \array_slice($skills, 0, self::ELEMENTS_MAX);

        return $this;
    }

    /** @return list<string> */
    public function getHardSkills(): array
    {
        return $this->hardSkills;
    }

    /** @param list<string> $skills */
    public function setHardSkills(array $skills): static
    {
        $this->hardSkills = \array_slice($skills, 0, self::ELEMENTS_MAX);

        return $this;
    }

    /** @return list<array{type: string, url: string}> */
    public function getReseaux(): array
    {
        return $this->reseaux;
    }

    /** @param list<array{type: string, url: string}> $reseaux */
    public function setReseaux(array $reseaux): static
    {
        $this->reseaux = \array_slice($reseaux, 0, 10);

        return $this;
    }

    /** @return Collection<int, CvExperience> */
    public function getExperiences(): Collection
    {
        return $this->experiences;
    }

    /** @return Collection<int, CvFormation> */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    /** @param list<CvExperience> $experiences */
    public function remplacerExperiences(array $experiences): void
    {
        $this->experiences->clear();
        foreach (array_values($experiences) as $i => $e) {
            $e->setOrdre($i);
            $this->experiences->add($e);
        }
    }

    /** @param list<CvFormation> $formations */
    public function remplacerFormations(array $formations): void
    {
        $this->formations->clear();
        foreach (array_values($formations) as $i => $f) {
            $f->setOrdre($i);
            $this->formations->add($f);
        }
    }
}
