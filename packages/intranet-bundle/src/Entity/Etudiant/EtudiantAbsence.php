<?php

namespace IntranetBundle\Entity\Etudiant;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use IntranetBundle\Repository\Etudiant\EtudiantAbsenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtudiantAbsenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(BooleanFilter::class, properties: ['justifiee'])]
#[ApiFilter(SearchFilter::class, properties: ['scolariteSemestre' => 'exact', 'event' => 'exact'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/administration/etudiant_absences',
            normalizationContext: ['groups' => ['absence:administration']],
        ),
        new Post()
    ]
)]
class EtudiantAbsence
{
    use UuidTrait;
    use EduSignTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $justifiee = false;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    private ?Personnel $personnel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateJustification = null;

    #[ORM\ManyToOne(inversedBy: 'absence')]
    private ?EtudiantAbsenceJustificatif $absenceJustificatif = null;

    #[ORM\ManyToOne(inversedBy: 'absence')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?EtudiantScolariteSemestre $scolariteSemestre = null;

    #[ORM\ManyToOne(inversedBy: 'absences')]
    #[ORM\JoinColumn(nullable: false)]
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
