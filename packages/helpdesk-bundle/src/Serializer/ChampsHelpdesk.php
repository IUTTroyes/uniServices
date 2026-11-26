<?php

declare(strict_types=1);

namespace HelpdeskBundle\Serializer;

use App\Entity\Structure\StructureService;
use App\Extension\ChampsSerialisesInterface;
use Doctrine\ORM\EntityManagerInterface;
use HelpdeskBundle\Entity\HelpdeskCategorie;

/** Catégories de tickets exposées sur les services de l'établissement. */
final class ChampsHelpdesk implements ChampsSerialisesInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function supporte(object $objet): bool
    {
        return $objet instanceof StructureService;
    }

    public function champs(object $objet, array $groupes): array
    {
        if (array_intersect($groupes, ['service:read', 'service:form_ticket']) === []) {
            return [];
        }

        return ['helpdeskCategories' => $this->em->getRepository(HelpdeskCategorie::class)->findBy(['service' => $objet])];
    }
}
