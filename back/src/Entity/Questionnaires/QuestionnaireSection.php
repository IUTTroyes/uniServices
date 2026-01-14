<?php

namespace App\Entity\Questionnaires;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\OptionTrait;
use App\Entity\Traits\UuidTrait;
use App\Enum\QuestTypeRepeatEnum;
use App\Enum\QuestTypeSectionEnum;
use App\Repository\Questionnaires\QuestionnaireSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: QuestionnaireSectionRepository::class)]
#[ApiResource(
    uriTemplate: '/questionnaires/{questionnaireId}/questionnaire_sections',
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['questionnaire_section:read']],),
        ],
    uriVariables: [
        'questionnaireId' => new Link(fromClass: Questionnaire::class, toProperty: 'questionnaire'),
    ]
)]
#[ApiResource(
    operations: [
        new Patch(),
        new Delete(),
        new Post()
    ]
)]
#[ORM\Index(name: 'idx_section_questionnaire_ordre', columns: ['questionnaire_id', 'sort_order'])]
class QuestionnaireSection
{
    use OptionTrait;
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireSections')]
    private ?Questionnaire $questionnaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true, enumType: QuestTypeSectionEnum::class)]
    #[Groups(['questionnaire_section:read'])]
    private ?QuestTypeSectionEnum $typeSection = null;

    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?int $sortOrder = null;

    /**
     * @var Collection<int, QuestionnaireQuestion>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireQuestion::class, mappedBy: 'section', cascade: ['persist', 'remove'])]
    #[Groups(['questionnaire_section:read'])]
    private Collection $questions;

    /**
     * @var Collection<int, QuestionnaireSectionInstance>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireSectionInstance::class, mappedBy: 'section', cascade: ['persist', 'remove'])]
    private Collection $sectionInstances;


    public function __construct()
    {
        $this->setOpt([]);
        $this->questions = new ArrayCollection();
        $this->sectionInstances = new ArrayCollection();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
//        $resolver->setDefaults([
//            'materiel' => false,
//            'edt' => false,
//            'stage' => false,
//            'resp_ri' => '',
//        ]);
//
//        $resolver->setAllowedTypes('materiel', 'bool');
//        $resolver->setAllowedTypes('edt', 'bool');
//        $resolver->setAllowedTypes('stage', 'bool');
//        $resolver->setAllowedTypes('resp_ri', 'string'); //todo: sauvegarder l'IRI de la personne ? pour faire le lien en front ?
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionnaire(): ?Questionnaire
    {
        return $this->questionnaire;
    }

    public function setQuestionnaire(?Questionnaire $questionnaire): static
    {
        $this->questionnaire = $questionnaire;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

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

    public function getTypeSection(): ?QuestTypeSectionEnum
    {
        return $this->typeSection;
    }

    public function setTypeSection(?QuestTypeSectionEnum $typeSection): static
    {
        $this->typeSection = $typeSection;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestionnaireQuestion(QuestionnaireQuestion $questionnaireQuestion): static
    {
        if (!$this->questions->contains($questionnaireQuestion)) {
            $this->questions->add($questionnaireQuestion);
            $questionnaireQuestion->setSection($this);
        }

        return $this;
    }

    public function removeQuestionnaireQuestion(QuestionnaireQuestion $questionnaireQuestion): static
    {
        if ($this->questions->removeElement($questionnaireQuestion)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireQuestion->getSection() === $this) {
                $questionnaireQuestion->setSection(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireSectionInstance>
     */
    public function getSectionInstances(): Collection
    {
        return $this->sectionInstances;
    }

    public function addQuestionnaireSectionInstance(QuestionnaireSectionInstance $questionnaireSectionInstance): static
    {
        if (!$this->sectionInstances->contains($questionnaireSectionInstance)) {
            $this->sectionInstances->add($questionnaireSectionInstance);
            $questionnaireSectionInstance->setSection($this);
        }

        return $this;
    }

    public function removeQuestionnaireSectionInstance(QuestionnaireSectionInstance $questionnaireSectionInstance): static
    {
        if ($this->sectionInstances->removeElement($questionnaireSectionInstance)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireSectionInstance->getSection() === $this) {
                $questionnaireSectionInstance->setSection(null);
            }
        }

        return $this;
    }

    public function getRepeatSource(): ?QuestTypeRepeatEnum
    {
        //si section configurable, on retourne le type de répétition qui est dans opt
        $opts = $this->getOpt();
        if (!array_key_exists('repeat_source', $opts) || $opts['repeat_source'] === null) {
            return null;
        }
        return QuestTypeRepeatEnum::tryFrom($opts['repeat_source']);
    }
}
