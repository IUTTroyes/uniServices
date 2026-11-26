<?php

declare(strict_types=1);

namespace HelpdeskBundle\Service;

use App\Entity\Structure\StructureService;
use App\Extension\SuppressionBloqueeInterface;
use Doctrine\ORM\EntityManagerInterface;
use HelpdeskBundle\Entity\HelpdeskCategorie;

/** Un service qui porte des catégories de tickets ne se supprime pas. */
final class ServiceUtiliseParHelpdesk implements SuppressionBloqueeInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function bloque(object $objet): bool
    {
        return $objet instanceof StructureService
            && $this->em->getRepository(HelpdeskCategorie::class)->findOneBy(['service' => $objet]) !== null;
    }
}
