<?php

declare(strict_types=1);

namespace IntranetBundle\Entity\Commission;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'intranet_sous_commission_revision')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMMISSION_REVISION', columns: ['sous_commission_id', 'numero'])]
class SousCommissionRevision implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private SousCommission $sousCommission;

    #[ORM\Column]
    private int $numero;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Personnel $validePar;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $valideLe;

    #[ORM\Column(type: 'json')]
    private array $vue;

    #[ORM\Column(type: 'json')]
    private array $sources;

    #[ORM\Column(length: 64)]
    private string $empreinte;

    #[ORM\ManyToOne]
    private ?Personnel $rouvertPar = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $invalideeLe = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifReouverture = null;

    public function __construct(SousCommission $commission, int $numero, Personnel $auteur, array $vue, array $sources, string $empreinte)
    {
        $this->sousCommission = $commission;
        $this->numero = $numero;
        $this->validePar = $auteur;
        $this->valideLe = new \DateTimeImmutable();
        $this->vue = $vue;
        $this->sources = $sources;
        $this->empreinte = $empreinte;
    }
    public function getId(): ?int { return $this->id; }
    public function getSousCommission(): SousCommission { return $this->sousCommission; }
    public function getNumero(): int { return $this->numero; }
    public function getVue(): array { return $this->vue; }
    public function getSources(): array { return $this->sources; }
    public function getEmpreinte(): string { return $this->empreinte; }
    public function isInvalidee(): bool { return $this->invalideeLe !== null; }
    public function invalider(Personnel $auteur, string $motif): void
    {
        if ($this->isInvalidee()) throw new \LogicException('Révision déjà invalidée.');
        $this->rouvertPar = $auteur;
        $this->invalideeLe = new \DateTimeImmutable();
        $this->motifReouverture = $motif;
    }
    public function metadata(): array
    {
        return ['id' => $this->id, 'numero' => $this->numero,
            'validePar' => ['id' => $this->validePar->getId(), 'nom' => $this->validePar->getNom(), 'prenom' => $this->validePar->getPrenom()],
            'valideLe' => $this->valideLe->format(DATE_ATOM),
            'invalideeLe' => $this->invalideeLe?->format(DATE_ATOM),
            'rouvertPar' => $this->rouvertPar?->getId(), 'motifReouverture' => $this->motifReouverture];
    }
}
