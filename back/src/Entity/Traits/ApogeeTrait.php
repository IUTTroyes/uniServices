<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait ApogeeTrait
{
    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(['maquette:detail', 'enseignement:detail'])]
    private ?string $codeApogee = null;

    public function getCodeApogee(): ?string
    {
        return $this->codeApogee;
    }

    public function setCodeApogee(?string $codeApogee): static
    {
        $this->codeApogee = $codeApogee;

        return $this;
    }
}
