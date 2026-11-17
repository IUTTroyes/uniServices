<?php

declare(strict_types=1);

namespace IntranetBundle\Entity\Commission;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'intranet_resultat_sous_commission')]
#[ORM\UniqueConstraint(name: 'UNIQ_REVISION_INSCRIPTION', columns: ['revision_id', 'inscription_id'])]
class ResultatSousCommission
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private SousCommissionRevision $revision;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private EtudiantScolariteSemestre $inscription;

    /** Moyennes d’enseignement et d’UE, moyenne générale et rang figés. */
    #[ORM\Column(type: 'json')]
    private array $resultat;

    #[ORM\Column(nullable: true)]
    private ?bool $propositionCalculee;

    #[ORM\Column]
    private bool $decisionJury;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifJury;

    public function __construct(SousCommissionRevision $revision, EtudiantScolariteSemestre $inscription, array $resultat, bool $decision, ?string $motif)
    {
        $this->revision = $revision;
        $this->inscription = $inscription;
        $this->resultat = $resultat;
        $this->propositionCalculee = $resultat['decision'];
        $this->decisionJury = $decision;
        $this->motifJury = $motif;
    }
    public function getRevision(): SousCommissionRevision { return $this->revision; }
    public function getInscription(): EtudiantScolariteSemestre { return $this->inscription; }
}
