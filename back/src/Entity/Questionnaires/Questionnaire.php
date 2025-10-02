<?php

namespace App\Entity\Questionnaires;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OptionTrait;
use App\Repository\Questionnaires\QuestionnaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuestionnaireRepository::class)]
#[ApiResource()]
#[ORM\HasLifecycleCallbacks]
class Questionnaire
{
    use LifeCycleTrait;
    use OptionTrait;

    #[ORM\Column(type: UuidType::NAME)]
    #[ApiProperty(identifier: true)]
    private Uuid $uuid;

    public function getUuidString(): string
    {
        return (string)$this->getUuid();
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): void
    {
        $this->uuid = $uuid;
    }


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateOuverture = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFermeture = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texteDebut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texteFin = null;

    /**
     * @var Collection<int, QuestionnaireSection>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireSection::class, mappedBy: 'questionnaire')]
    private Collection $questionnaireSections;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    public function __construct()
    {
        $this->setOpt([]);
        $this->questionnaireSections = new ArrayCollection();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'anonymous' => true,
            'autoSave' => true,
            'allowBack' => true,
            'showProgress' => true,
            'requireCompletion' => false,
        ]);

        $resolver->setAllowedTypes('anonymous', 'bool');
        $resolver->setAllowedTypes('autoSave', 'bool');
        $resolver->setAllowedTypes('allowBack', 'bool');
        $resolver->setAllowedTypes('showProgress', 'bool');
        $resolver->setAllowedTypes('requireCompletion', 'bool');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

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

    public function getDateOuverture(): ?\DateTimeInterface
    {
        return $this->dateOuverture;
    }

    public function setDateOuverture(?\DateTimeInterface $dateOuverture): static
    {
        $this->dateOuverture = $dateOuverture;

        return $this;
    }

    public function getDateFermeture(): ?\DateTimeInterface
    {
        return $this->dateFermeture;
    }

    public function setDateFermeture(?\DateTimeInterface $dateFermeture): static
    {
        $this->dateFermeture = $dateFermeture;

        return $this;
    }

    public function getTexteDebut(): ?string
    {
        return $this->texteDebut;
    }

    public function setTexteDebut(?string $texteDebut): static
    {
        $this->texteDebut = $texteDebut;

        return $this;
    }

    public function getTexteFin(): ?string
    {
        return $this->texteFin;
    }

    public function setTexteFin(?string $texteFin): static
    {
        $this->texteFin = $texteFin;

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireSection>
     */
    public function getQuestionnaireSections(): Collection
    {
        return $this->questionnaireSections;
    }

    public function addQuestionnaireSection(QuestionnaireSection $questionnaireSection): static
    {
        if (!$this->questionnaireSections->contains($questionnaireSection)) {
            $this->questionnaireSections->add($questionnaireSection);
            $questionnaireSection->setQuestionnaire($this);
        }

        return $this;
    }

    public function removeQuestionnaireSection(QuestionnaireSection $questionnaireSection): static
    {
        if ($this->questionnaireSections->removeElement($questionnaireSection)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireSection->getQuestionnaire() === $this) {
                $questionnaireSection->setQuestionnaire(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
