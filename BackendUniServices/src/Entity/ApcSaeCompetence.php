<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Entity/ApcSaeCompetence.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 26/05/2022 18:16
 */

namespace App\Entity;

use App\Entity\Traits\LifeCycleTrait;
use App\Repository\ApcSaeCompetenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApcSaeCompetenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ApcSaeCompetence extends BaseEntity
{
    use LifeCycleTrait;

    #[ORM\Column(type: Types::FLOAT)]
    private float $coefficient = 0;

    public function __construct(#[ORM\ManyToOne(targetEntity: ApcSae::class, inversedBy: 'apcSaeCompetences')] private ?ApcSae $sae, #[ORM\ManyToOne(targetEntity: ApcCompetence::class, inversedBy: 'apcSaeCompetences')] private ?ApcCompetence $competence)
    {
    }

    public function getSae(): ?ApcSae
    {
        return $this->sae;
    }

    public function setSae(?ApcSae $sae): self
    {
        $this->sae = $sae;

        return $this;
    }

    public function getCompetence(): ?ApcCompetence
    {
        return $this->competence;
    }

    public function setCompetence(?ApcCompetence $competence): self
    {
        $this->competence = $competence;

        return $this;
    }

    public function getCoefficient(): ?float
    {
        return $this->coefficient;
    }

    public function setCoefficient(float $coefficient): self
    {
        $this->coefficient = $coefficient;

        return $this;
    }
}
