<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait EduSignTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['semestre:read'])]
    private ?string $keyEduSign = null;

    public function getKeyEduSign(): ?string
    {
        return $this->keyEduSign;
    }

    public function setKeyEduSign(?string $keyEduSign): static
    {
        $this->keyEduSign = $keyEduSign;

        return $this;
    }
}
