<?php

namespace App\Entity\Questionnaires;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\OptionTrait;
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
    uriVariables: [
        'questionnaireId' => new Link(fromClass: Questionnaire::class, toProperty: 'questionnaire'),
    ],
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['questionnaire_section:read']],),
        ]
)]
#[ApiResource(
    operations: [
        new Patch(),
        new Delete(),
        new Post()
    ]
)]
class QuestionnaireSection
{
    use OptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireSections')]
    private ?Questionnaire $questionnaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire_section:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true, enumType: QuestTypeSectionEnum::class)]
    #[Groups(['questionnaire_section:read'])]
    private ?QuestTypeSectionEnum $typeSection = null;

    #[ORM\Column]
    #[Groups(['questionnaire_section:read'])]
    private ?int $ordre = null;

    /**
     * @var Collection<int, QuestionnaireQuestion>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireQuestion::class, mappedBy: 'section')]
    #[Groups(['questionnaire_section:read'])]
    private Collection $questionnaireQuestions;


    public function __construct()
    {
        $this->setOpt([]);
        $this->questionnaireQuestions = new ArrayCollection();
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

    public function getTypeSection(): ?QuestTypeSectionEnum
    {
        return $this->typeSection;
    }

    public function setTypeSection(?QuestTypeSectionEnum $typeSection): static
    {
        $this->typeSection = $typeSection;

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

    /**
     * @return Collection<int, QuestionnaireQuestion>
     */
    public function getQuestionnaireQuestions(): Collection
    {
        return $this->questionnaireQuestions;
    }

    public function addQuestionnaireQuestion(QuestionnaireQuestion $questionnaireQuestion): static
    {
        if (!$this->questionnaireQuestions->contains($questionnaireQuestion)) {
            $this->questionnaireQuestions->add($questionnaireQuestion);
            $questionnaireQuestion->setSection($this);
        }

        return $this;
    }

    public function removeQuestionnaireQuestion(QuestionnaireQuestion $questionnaireQuestion): static
    {
        if ($this->questionnaireQuestions->removeElement($questionnaireQuestion)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireQuestion->getSection() === $this) {
                $questionnaireQuestion->setSection(null);
            }
        }

        return $this;
    }
}
