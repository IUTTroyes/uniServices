<?php

namespace IntranetBundle\Entity\Etudiant;


use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Personnel;
use IntranetBundle\Filter\AbsenceFilter;
use IntranetBundle\Repository\Etudiant\EtudiantAbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use IntranetBundle\State\Provider\Absence\AbsenceStatsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtudiantAbsenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(AbsenceFilter::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/administration/etudiant_absences',
            normalizationContext: ['groups' => ['absence:administration']],
        ),
        new GetCollection(
            uriTemplate: '/administration/last/etudiant_absences',
            normalizationContext: ['groups' => ['absence:administration']],
        ),
        new GetCollection(
            uriTemplate: '/administration/stats/etudiant_absences',
            paginationEnabled: false,
            normalizationContext: ['groups' => ['absence:administration']],
            provider: AbsenceStatsProvider::class
        ),
        new GetCollection(
            uriTemplate: '/administration/repartition/etudiant_absences',
            normalizationContext: ['groups' => ['absence:administration']],
        ),
        new Post()
    ],
    order: ['created' => 'ASC']
)]
class EtudiantAbsence
{
    use UuidTrait;
    use EduSignTrait;
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['absence:administration'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['absence:administration'])]
    private bool $justifiee = false;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    #[Groups(['absence:administration'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['absence:administration'])]
    private ?\DateTimeInterface $dateJustification = null;

    #[ORM\ManyToOne(inversedBy: 'absence')]
    #[Groups(['absence:administration'])]
    private ?EtudiantAbsenceJustificatif $absenceJustificatif = null;

    #[ORM\ManyToOne(inversedBy: 'absence')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['absence:administration'])]
    private ?EtudiantScolariteSemestre $scolariteSemestre = null;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['absence:administration'])]
    private ?EdtEvent $event = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isJustifiee(): ?bool
    {
        return $this->justifiee;
    }

    public function setJustifiee(bool $justifiee): static
    {
        $this->justifiee = $justifiee;

        return $this;
    }

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getDateJustification(): ?\DateTimeInterface
    {
        return $this->dateJustification;
    }

    public function setDateJustification(?\DateTimeInterface $dateJustification): static
    {
        $this->dateJustification = $dateJustification;

        return $this;
    }

    public function getAbsenceJustificatif(): ?EtudiantAbsenceJustificatif
    {
        return $this->absenceJustificatif;
    }

    public function setAbsenceJustificatif(?EtudiantAbsenceJustificatif $absenceJustificatif): static
    {
        $this->absenceJustificatif = $absenceJustificatif;

        return $this;
    }

    public function getScolariteSemestre(): ?EtudiantScolariteSemestre
    {
        return $this->scolariteSemestre;
    }

    public function setScolariteSemestre(?EtudiantScolariteSemestre $scolariteSemestre): static
    {
        $this->scolariteSemestre = $scolariteSemestre;

        return $this;
    }

    public function getEvent(): ?EdtEvent
    {
        return $this->event;
    }

    public function setEvent(?EdtEvent $event): static
    {
        $this->event = $event;

        return $this;
    }
}
