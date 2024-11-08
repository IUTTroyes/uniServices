<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Entity/AlternanceFicheSuivi.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 14/05/2022 10:52
 */

namespace App\Entity;

use App\Repository\AlternanceFicheSuiviRepository;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Exception;

#[ORM\Entity(repositoryClass: AlternanceFicheSuiviRepository::class)]
class AlternanceFicheSuivi extends AbstractFicheSuivi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Alternance::class, inversedBy: 'alternanceFicheSuivis')]
    private ?Alternance $alternance = null;

    /**
     * @throws Exception
     */
    public function __construct(Alternance $alternance)
    {
        $this->setAlternance($alternance);
        $this->setDate(Carbon::today());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlternance(): ?Alternance
    {
        return $this->alternance;
    }

    public function setAlternance(?Alternance $alternance): self
    {
        $this->alternance = $alternance;

        return $this;
    }
}
