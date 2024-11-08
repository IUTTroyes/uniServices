<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Entity/CodeInsee.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 26/05/2022 18:18
 */

namespace App\Entity;

use App\Repository\CodeInseeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodeInseeRepository::class)]
class CodeInsee extends BaseEntity
{
    #[ORM\Column(type: Types::STRING, length: 6)]
    private ?string $codeInsee = null;

    #[ORM\Column(type: Types::STRING, length: 60)]
    private ?string $codePostal = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $ville = null;

    public function getCodeInsee(): ?string
    {
        return $this->codeInsee;
    }

    public function setCodeInsee(string $codeInsee): self
    {
        $this->codeInsee = $codeInsee;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }
}
