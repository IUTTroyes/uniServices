<?php

namespace FinanceBundle\Service\Notification;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use App\Security\PermissionResolver;
use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Security\FinanceAccess;

/**
 * Destinataires des notifications du package : personnels qui ont EXPLICITEMENT un rôle du package dans un département
 * (rôle attribué ou hérité, package « finance » actif). Le super administrateur, qui a tous les droits partout, n'est pas
 * destinataire à ce seul titre : il ne reçoit rien qu'il n'ait demandé.
 */
class FinanceDestinataires
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PermissionResolver $resolver,
    ) {
    }

    /**
     * Personnels ayant ce rôle dans le département. Le service financier (ROLE_FINANCE_MANAGER) couvre tout
     * l'établissement : ses gestionnaires sont destinataires quel que soit le département. Sans département (demande
     * imputée à un service), c'est aussi lui qui instruit, quel que soit le rôle demandé.
     *
     * @return list<Personnel>
     */
    public function avecRole(?StructureDepartement $departement, string $role): array
    {
        if ($departement === null || $role === FinanceAccess::ROLE_GESTIONNAIRE) {
            return array_values(array_map(
                static fn (array $e): Personnel => $e['personnel'],
                $this->parRole([FinanceAccess::ROLE_GESTIONNAIRE]),
            ));
        }
        $personnels = [];
        foreach ($this->em->getRepository(StructureDepartementPersonnel::class)->findBy(['departement' => $departement]) as $affectation) {
            $personnel = $affectation->getPersonnel();
            if ($personnel?->getId() !== null && \in_array($role, $this->resolver->resolveForDepartment($affectation), true)) {
                $personnels[$personnel->getId()] = $personnel;
            }
        }

        return array_values($personnels);
    }

    /**
     * Pour le récapitulatif : chaque personnel ayant l'un des rôles quelque part, avec ses départements par rôle.
     *
     * @param list<string> $roles
     *
     * @return array<int, array{personnel: Personnel, roles: array<string, list<int>>}> indexé par id du personnel
     */
    public function parRole(array $roles): array
    {
        $resultat = [];
        $affectations = $this->em->getRepository(StructureDepartementPersonnel::class)->createQueryBuilder('dp')
            ->join('dp.personnel', 'p')->addSelect('p')
            ->join('dp.departement', 'd')->addSelect('d')
            ->getQuery()->toIterable();
        foreach ($affectations as $affectation) {
            \assert($affectation instanceof StructureDepartementPersonnel);
            $resolus = $this->resolver->resolveForDepartment($affectation);
            foreach ($roles as $role) {
                if (!\in_array($role, $resolus, true)) {
                    continue;
                }
                $personnel = $affectation->getPersonnel();
                $id = (int) $personnel->getId();
                $resultat[$id] ??= ['personnel' => $personnel, 'roles' => []];
                $resultat[$id]['roles'][$role][] = (int) $affectation->getDepartement()->getId();
            }
        }

        return $resultat;
    }
}
