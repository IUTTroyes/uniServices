<?php

namespace UnifolioBundle\Entity;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Repository\CampagneAffectationRepository;

/** Un évaluateur est chargé d'un étudiant pour une campagne (plusieurs évaluateurs possibles par étudiant). */
#[ORM\Entity(repositoryClass: CampagneAffectationRepository::class)]
#[ORM\Table(name: 'unifolio_campagne_affectation')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_affectation', columns: ['campagne_id', 'evaluateur_id', 'etudiant_id'])]
#[ORM\Index(name: 'idx_unifolio_affectation_evaluateur', columns: ['evaluateur_id'])]
class CampagneAffectation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Campagne $campagne;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Personnel $evaluateur;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Etudiant $etudiant;

    public function __construct(Campagne $campagne, Personnel $evaluateur, Etudiant $etudiant)
    {
        $this->campagne = $campagne;
        $this->evaluateur = $evaluateur;
        $this->etudiant = $etudiant;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampagne(): Campagne
    {
        return $this->campagne;
    }

    public function getEvaluateur(): Personnel
    {
        return $this->evaluateur;
    }

    public function getEtudiant(): Etudiant
    {
        return $this->etudiant;
    }
}
