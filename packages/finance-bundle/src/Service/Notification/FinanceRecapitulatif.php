<?php

namespace FinanceBundle\Service\Notification;

use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum as Statut;
use FinanceBundle\Security\FinanceAccess;

/**
 * Contenu du récapitulatif des demandes en attente (finance:recapitulatif), en valeurs simples pour l'e-mail :
 *  - service financier (ROLE_FINANCE_MANAGER) : toutes les demandes en cours de ses départements, par statut ;
 *  - valideur (ROLE_FINANCE_VALIDEUR) : les demandes soumises de ses départements, qui attendent son avis.
 */
class FinanceRecapitulatif
{
    /** Lignes détaillées par statut dans l'e-mail (au-delà : « et N autres »). */
    public const LIGNES_PAR_STATUT = 10;

    /** Statuts suivis par le service financier, dans l'ordre du circuit. */
    private const STATUTS_GESTIONNAIRE = [
        Statut::SOUMIS, Statut::AVIS_DIRECTION, Statut::VERIFICATION_FINANCIERE, Statut::VISA_ENGAGEMENT, Statut::COMMANDE_VALIDEE,
        Statut::COMMANDE_TRANSMISE, Statut::SERVICE_FAIT_CONSTATE, Statut::SERVICE_FAIT_CERTIFIE, Statut::FACTURE_RECUE, Statut::PAYE,
    ];
    private const STATUTS_VALIDEUR = [Statut::SOUMIS, Statut::AVIS_DIRECTION];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @param array<string, list<int>> $roles rôle → départements (cf. FinanceDestinataires::parRole)
     *
     * @return array{total: int, resume: string, groupes: list<array{statut: string, nombre: int, lignes: list<array<string, string>>, autres: int}>}|null
     *         null si rien n'est en attente
     */
    public function pour(array $roles, ?\DateTimeImmutable $maintenant = null): ?array
    {
        $maintenant ??= new \DateTimeImmutable();
        $parStatut = [];
        foreach ([[FinanceAccess::ROLE_GESTIONNAIRE, self::STATUTS_GESTIONNAIRE], [FinanceAccess::ROLE_VALIDEUR, self::STATUTS_VALIDEUR]] as [$role, $statuts]) {
            $departements = $roles[$role] ?? [];
            if ($departements === []) {
                continue;
            }
            $commandes = $this->em->getRepository(FinanceBonCommande::class)->createQueryBuilder('c')
                ->leftJoin('c.departement', 'd')->addSelect('d')
                ->where('IDENTITY(c.departement) IN (:departements)')
                ->andWhere('c.statut IN (:statuts)')
                ->setParameter('departements', array_values(array_unique($departements)))
                ->setParameter('statuts', array_map(static fn (Statut $s): string => $s->value, $statuts))
                ->orderBy('c.updatedAt', 'ASC')
                ->getQuery()->getResult();
            foreach ($commandes as $c) {
                $parStatut[$c->getStatut()->value][$c->getId()] = $c;
            }
        }
        if ($parStatut === []) {
            return null;
        }

        $groupes = [];
        $resume = [];
        $total = 0;
        foreach (Statut::cases() as $statut) {
            $liste = array_values($parStatut[$statut->value] ?? []);
            if ($liste === []) {
                continue;
            }
            $total += \count($liste);
            $resume[] = sprintf('%d « %s »', \count($liste), mb_strtolower($statut->getLabel()));
            $groupes[] = [
                'statut' => $statut->getLabel(),
                'nombre' => \count($liste),
                'lignes' => array_map(fn (FinanceBonCommande $c): array => $this->ligne($c, $maintenant), \array_slice($liste, 0, self::LIGNES_PAR_STATUT)),
                'autres' => max(0, \count($liste) - self::LIGNES_PAR_STATUT),
            ];
        }

        return ['total' => $total, 'resume' => implode(', ', $resume), 'groupes' => $groupes];
    }

    /** @return array<string, string> */
    private function ligne(FinanceBonCommande $c, \DateTimeImmutable $maintenant): array
    {
        $depuis = $c->getUpdatedAt() ?? $c->getDateDemande();
        $jours = $depuis !== null ? (int) $maintenant->diff(\DateTimeImmutable::createFromInterface($depuis))->days : 0;
        $montant = $c->getMontantTtc() ?? $c->getMontantHt();

        return [
            'objet' => mb_strimwidth((string) $c->getObjetDemande(), 0, 90, '…'),
            'departement' => (string) ($c->getDepartement()?->getLibelle() ?? '—'),
            'demandeur' => (string) ($c->getDemandeur()?->getDisplay() ?? '—'),
            'montant' => $montant === null ? '—' : number_format((float) $montant, 2, ',', ' ').' €',
            'numero' => (string) ($c->getNumeroBonCommande() ?? ''),
            'depuis' => $jours === 0 ? 'aujourd\'hui' : sprintf('depuis %d jour%s', $jours, $jours > 1 ? 's' : ''),
            'lien' => FinanceNotifier::url($c),
        ];
    }
}
