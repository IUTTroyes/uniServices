<?php

declare(strict_types=1);

namespace IntranetBundle\Serializer;

use App\Entity\Edt\EdtEvent;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Users\Personnel;
use App\Extension\ChampsSerialisesInterface;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Entity\Edt\EdtAppel;
use IntranetBundle\Entity\Previsionnel\Previsionnel;

/** Champs de l'intranet exposés sur des ressources du cœur. */
final class ChampsIntranet implements ChampsSerialisesInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function supporte(object $objet): bool
    {
        return $objet instanceof ScolEnseignement || $objet instanceof Personnel || $objet instanceof EdtEvent;
    }

    public function champs(object $objet, array $groupes): array
    {
        if ($objet instanceof ScolEnseignement && array_intersect($groupes, ['enseignement:detail', 'edt_event:read:agenda']) !== []) {
            return ['previsionnels' => $this->em->getRepository(Previsionnel::class)->findBy(['enseignement' => $objet])];
        }
        if ($objet instanceof Personnel && in_array('previsionnel:detail', $groupes, true)) {
            return ['previsionnels' => $this->em->getRepository(Previsionnel::class)->findBy(['personnel' => $objet])];
        }
        if ($objet instanceof EdtEvent && in_array('edt_pointage:read', $groupes, true)) {
            return ['appel' => $this->em->getRepository(EdtAppel::class)->findOneBy(['edtEvent' => $objet])];
        }

        return [];
    }
}
