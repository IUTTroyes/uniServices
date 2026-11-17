<?php

declare(strict_types=1);

namespace IntranetBundle\Entity\Commission;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'intranet_sous_commission')]
class SousCommission implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private StructureSemestre $semestre;

    #[ORM\Column]
    private bool $validee = false;

    #[ORM\Column]
    private int $numeroRevision = 0;

    public function __construct(StructureSemestre $semestre) { $this->semestre = $semestre; }
    public function getId(): ?int { return $this->id; }
    public function getSemestre(): StructureSemestre { return $this->semestre; }
    public function isValidee(): bool { return $this->validee; }
    public function getNumeroRevision(): int { return $this->numeroRevision; }
    public function valider(): int { $this->validee = true; return ++$this->numeroRevision; }
    public function rouvrir(): void { $this->validee = false; }
}
