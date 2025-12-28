<?php

namespace App\Entity\Questionnaires;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\OptionTrait;
use App\Enum\QuestTypeQuestionEnum;
use App\Repository\Questionnaires\QuestionnaireQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuestionnaireQuestionRepository::class)]
#[ApiResource(
    uriTemplate: '/questionnaire_sections/{questionnaireSectionId}/questionnaire_questions',
    operations: [
        new GetCollection(),
        ],
    uriVariables: [
        'questionnaireSectionId' => new Link(toProperty: 'section', fromClass: QuestionnaireSection::class),
    ]
)]
#[ApiResource(
    operations: [
        new Patch(),
        new Delete(),
        new Post()
    ]
)]
#[ORM\Index(name: 'idx_question_section_ordre', columns: ['section_id', 'sort_order'])]
class QuestionnaireQuestion
{
    use OptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['questionnaire_section:read'])]
    private ?string $label = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?string $help = null;

    #[ORM\Column(nullable: true, enumType: QuestTypeQuestionEnum::class)]
    #[Groups(['questionnaire_section:read'])]
    private ?QuestTypeQuestionEnum $typeQuestion = null;

    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?bool $required = true;

    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?int $sortOrder = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?array $choices = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireQuestions')]
    private ?QuestionnaireSection $section = null;

    #[ORM\Column(type: UuidType::NAME)]
    #[ApiProperty(identifier: true)]
    #[Groups(['questionnaire_section:read'])]
    private Uuid $uuid;

    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?array $conditionalRules = null;

    /**
     * @var Collection<int, QuestionnaireAnswer>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireAnswer::class, mappedBy: 'question')]
    private Collection $answers;

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

    public function __construct()
    {
        $this->setOpt([]);
        $this->uuid = Uuid::v4(); // Génère un nouvel UUID à chaque création
        $this->answers = new ArrayCollection();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // You can define default options here if needed
        // $resolver->setDefaults([
        //     'some_option' => 'default_value',
        // ]);

        // You can also set allowed types for options
        // $resolver->setAllowedTypes('some_option', 'string');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): static
    {
        $this->help = $help;

        return $this;
    }

    public function getTypeQuestion(): ?QuestTypeQuestionEnum
    {
        return $this->typeQuestion;
    }

    public function setTypeQuestion(?QuestTypeQuestionEnum $typeQuestion): static
    {
        $this->typeQuestion = $typeQuestion;

        return $this;
    }

    public function isObligatoire(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;

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

    public function getChoices(): ?array
    {
        return $this->choices ?? [];
    }

    public function setChoices(?array $choices): static
    {
        $this->choices = $choices;

        return $this;
    }

    public function getSection(): ?QuestionnaireSection
    {
        return $this->section;
    }

    public function setSection(?QuestionnaireSection $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getConditionalRules(): ?array
    {
        return $this->conditionalRules ?? [];
    }

    public function setConditionalRules(?array $conditionalRules): static
    {
        $this->conditionalRules = $conditionalRules;

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addQuestionnaireReponse(QuestionnaireAnswer $questionnaireReponse): static
    {
        if (!$this->answers->contains($questionnaireReponse)) {
            $this->answers->add($questionnaireReponse);
            $questionnaireReponse->setQuestion($this);
        }

        return $this;
    }

    public function removeQuestionnaireReponse(QuestionnaireAnswer $questionnaireReponse): static
    {
        if ($this->answers->removeElement($questionnaireReponse)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireReponse->getQuestion() === $this) {
                $questionnaireReponse->setQuestion(null);
            }
        }

        return $this;
    }
}
