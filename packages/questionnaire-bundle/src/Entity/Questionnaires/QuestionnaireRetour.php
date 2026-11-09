<?php

namespace QuestionnaireBundle\Entity\Questionnaires;

use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;

/**
 * Retour d'un enseignant sur les résultats d'une section qui le concerne : commentaire et plan d'action, lus par
 * l'équipe qui gère le questionnaire (démarche qualité).
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_retour', columns: ['section_instance_id', 'personnel_id'])]
class QuestionnaireRetour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private QuestionnaireSectionInstance $sectionInstance;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Personnel $personnel;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $planAction = null;

    #[ORM\Column]
    private \DateTimeImmutable $modifieLe;

    public function __construct(QuestionnaireSectionInstance $sectionInstance, Personnel $personnel)
    {
        $this->sectionInstance = $sectionInstance;
        $this->personnel = $personnel;
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSectionInstance(): QuestionnaireSectionInstance
    {
        return $this->sectionInstance;
    }

    public function getPersonnel(): Personnel
    {
        return $this->personnel;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function getPlanAction(): ?string
    {
        return $this->planAction;
    }

    public function getModifieLe(): \DateTimeImmutable
    {
        return $this->modifieLe;
    }

    public function modifier(?string $commentaire, ?string $planAction): void
    {
        $this->commentaire = $commentaire !== null && trim($commentaire) !== '' ? trim($commentaire) : null;
        $this->planAction = $planAction !== null && trim($planAction) !== '' ? trim($planAction) : null;
        $this->modifieLe = new \DateTimeImmutable();
    }
}
