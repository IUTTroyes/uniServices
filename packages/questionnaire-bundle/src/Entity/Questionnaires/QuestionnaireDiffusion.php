<?php

namespace QuestionnaireBundle\Entity\Questionnaires;

use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un collègue destinataire des résultats d'une section publiée (pré-rempli depuis le prévisionnel ou l'emploi du
 * temps, ou ajouté à la main ; la liste est validée par l'équipe avant la publication des résultats).
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_diffusion', columns: ['section_instance_id', 'personnel_id'])]
class QuestionnaireDiffusion
{
    public const ORIGINE_PREVISIONNEL = 'previsionnel';
    public const ORIGINE_EDT = 'edt';
    public const ORIGINE_INTERVENANT = 'intervenant';
    public const ORIGINE_MANUELLE = 'manuelle';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'diffusions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private QuestionnaireSectionInstance $sectionInstance;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Personnel $personnel;

    #[ORM\Column(length: 20)]
    private string $origine;

    /** Notification « vos résultats sont disponibles » envoyée. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $notifieLe = null;

    public function __construct(QuestionnaireSectionInstance $sectionInstance, Personnel $personnel, string $origine)
    {
        $this->sectionInstance = $sectionInstance;
        $this->personnel = $personnel;
        $this->origine = $origine;
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

    public function getOrigine(): string
    {
        return $this->origine;
    }

    public function getNotifieLe(): ?\DateTimeImmutable
    {
        return $this->notifieLe;
    }

    public function marquerNotifie(): void
    {
        $this->notifieLe = new \DateTimeImmutable();
    }
}
