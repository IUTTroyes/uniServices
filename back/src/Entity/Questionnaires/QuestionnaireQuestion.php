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
        'questionnaireSectionId' => new Link(fromClass: QuestionnaireSection::class, toProperty: 'section'),
    ]
)]
#[ApiResource(
    operations: [
        new Patch(),
        new Delete(),
        new Post()
    ]
)]
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
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?string $help = null;

    #[ORM\Column(nullable: true, enumType: QuestTypeQuestionEnum::class)]
    #[Groups(['questionnaire_section:read'])]
    private ?QuestTypeQuestionEnum $typeQuestion = null;

    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?bool $obligatoire = true;

    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?int $ordre = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?array $parametres = [];

    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?array $reponses = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireQuestions')]
    private ?QuestionnaireSection $section = null;

    #[ORM\Column(type: UuidType::NAME)]
    #[ApiProperty(identifier: true)]
    #[Groups(['questionnaire_section:read'])]
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

    public function __construct()
    {
        $this->setOpt([]);
        $this->uuid = Uuid::v4(); // Génère un nouvel UUID à chaque création
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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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
        return $this->obligatoire;
    }

    public function setObligatoire(bool $obligatoire): static
    {
        $this->obligatoire = $obligatoire;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getParametres(): ?array
    {
        return $this->parametres ?? [];
    }

    public function setParametres(?array $parametres): static
    {
        $this->parametres = $parametres;

        return $this;
    }

    public function getReponses(): ?array
    {
        return $this->reponses ?? [];
    }

    public function setReponses(?array $reponses): static
    {
        $this->reponses = $reponses;

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
}
