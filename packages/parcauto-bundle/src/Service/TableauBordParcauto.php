<?php

declare(strict_types=1);

namespace ParcautoBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use ParcautoBundle\Entity\JournalVehicule;
use ParcautoBundle\Entity\Reservation;
use ParcautoBundle\Entity\Vehicule;

final readonly class TableauBordParcauto
{
    public function __construct(private EntityManagerInterface $em, private PlanningParcauto $planning)
    {
    }

    public function calculer(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        $vehicules = $this->em->getRepository(Vehicule::class)->findBy([], ['site' => 'ASC', 'libelle' => 'ASC']);
        $missions = $this->em->createQueryBuilder()
            ->select('r')->from(Reservation::class, 'r')
            ->where('r.departLe < :fin AND (r.retourLe >= :debut OR r.retourLe IS NULL)')
            ->setParameter('fin', $fin)->setParameter('debut', $debut)
            ->getQuery()->getResult();
        $journal = $this->em->createQueryBuilder()
            ->select('j')->from(JournalVehicule::class, 'j')
            ->where('j.date < :fin')
            ->andWhere('j.date >= :debut OR j.type IN (:pneus) OR (j.type = :incident AND j.date >= :debutIncidents)')
            ->setParameter('debut', $debut)->setParameter('fin', $fin)
            ->setParameter('pneus', [JournalVehicule::PNEUS, JournalVehicule::REMPLACEMENT_PNEUS])
            ->setParameter('incident', JournalVehicule::INCIDENT)
            ->setParameter('debutIncidents', min($fin, SaisieParcauto::maintenant())->modify('-90 days'))
            ->orderBy('j.date', 'ASC')->addOrderBy('j.id', 'ASC')
            ->getQuery()->getResult();

        $parVehicule = [];
        foreach ($vehicules as $vehicule) {
            $parVehicule[$vehicule->id] = [
                'secondes' => 0, 'kilometres' => 0, 'missions' => 0, 'depenses' => 0, 'pleins' => [], 'pneus' => [], 'incidents' => [],
            ];
        }
        foreach ($missions as $mission) {
            $id = $mission->vehicule->id;
            if (!isset($parVehicule[$id])) {
                continue;
            }
            $secondes = min($fin, $mission->retourLe ?? SaisieParcauto::maintenant())->getTimestamp()
                - max($debut, $mission->departLe)->getTimestamp();
            $parVehicule[$id]['secondes'] += max(0, $secondes);
            if ($mission->retourLe !== null && $mission->retourLe < $fin) {
                $parVehicule[$id]['kilometres'] += $mission->kilometrageRetour - $mission->kilometrageDepart;
                ++$parVehicule[$id]['missions'];
            }
        }

        $debutIncidents = min($fin, SaisieParcauto::maintenant())->modify('-90 days');
        foreach ($journal as $entree) {
            $id = $entree->vehicule->id;
            if (!isset($parVehicule[$id])) {
                continue;
            }
            $parVehicule[$id]['depenses'] += $entree->montantCentimes ?? 0;
            if ($entree->type === JournalVehicule::INCIDENT && $entree->date >= $debutIncidents) {
                $cle = mb_strtolower($entree->libelle);
                $parVehicule[$id]['incidents'][$cle][] = $entree;
            }
            if ($entree->type === JournalVehicule::CARBURANT) {
                $parVehicule[$id]['pleins'][] = $entree;
            }
            if ($entree->type === JournalVehicule::REMPLACEMENT_PNEUS) {
                unset($parVehicule[$id]['pneus'][$entree->libelle]);
            }
            if ($entree->type === JournalVehicule::PNEUS) {
                // Utiliser le même libellé pour les mesures successives d’un même train.
                $parVehicule[$id]['pneus'][$entree->libelle][] = $entree;
            }
        }

        $duree = max(1, $fin->getTimestamp() - $debut->getTimestamp());
        $lignes = [];
        $maintenant = SaisieParcauto::maintenant();
        $plans = $this->planning->echeancesParVehicule();
        foreach ($vehicules as $vehicule) {
            $donnees = $parVehicule[$vehicule->id];
            $distance = $donnees['kilometres'];
            $depenses = $donnees['depenses'] / 100;
            $amortissement = 0;
            if ($vehicule->misEnServiceLe !== null && $vehicule->reformePrevueLe !== null && $vehicule->reformePrevueLe > $vehicule->misEnServiceLe) {
                $perte = max(0, min($fin, $vehicule->reformePrevueLe)->getTimestamp() - max($debut, $vehicule->misEnServiceLe)->getTimestamp());
                $amortissement = ($vehicule->achatCentimes / 100) * $perte
                    / ($vehicule->reformePrevueLe->getTimestamp() - $vehicule->misEnServiceLe->getTimestamp());
            }
            $cout = $depenses + $amortissement;
            $pleins = $donnees['pleins'];
            $litres = array_sum(array_column($pleins, 'volumeMillilitres')) / 1000;
            $premier = $pleins[0] ?? null;
            $dernier = $pleins[array_key_last($pleins)] ?? null;
            $distancePleins = ($dernier->kilometrage ?? 0) - ($premier->kilometrage ?? 0);
            $consommation = $premier !== null && $distancePleins > 0 ? round(100 * ($litres - $premier->volumeMillilitres / 1000) / $distancePleins, 2) : null;
            $reforme = $vehicule->reformePrevueLe;
            if ($distance > 0 && $vehicule->reformeKm !== null) {
                $secondesRestantes = (int) min(3155760000, $duree * max(0, $vehicule->reformeKm - $vehicule->kilometrage) / $distance);
                $projection = SaisieParcauto::maintenant()->modify("+$secondesRestantes seconds");
                $reforme = $reforme === null ? $projection : min($projection, $reforme);
            }
            $alertes = [];
            foreach ($donnees['incidents'] as $incidents) {
                if (count($incidents) >= 2) {
                    $alertes[] = sprintf('%s · %d incidents en 90 jours', $incidents[0]->libelle, count($incidents));
                }
            }
            foreach ($donnees['pneus'] as $train => $releves) {
                $usure = $this->projectionPneus($releves, $vehicule->kilometrage);
                if ($usure !== null) {
                    $alertes[] = sprintf('%s · seuil de 2 mm projeté dans %d km', $train, $usure);
                }
            }
            if ($consommation !== null && $vehicule->consommationReference !== null && $consommation > $vehicule->consommationReference / 10 * 1.2) {
                $alertes[] = 'Consommation supérieure de plus de 20 % à la référence.';
            }
            foreach ($plans[$vehicule->id] ?? [] as $plan) {
                $kmRestants = $plan->echeanceKm === null ? null : $plan->echeanceKm - $vehicule->kilometrage;
                $secondesAvant = $plan->echeance->getTimestamp() - $maintenant->getTimestamp();
                if ($kmRestants !== null && $distance > 0) {
                    $secondesAvant = min($secondesAvant, $duree * $kmRestants / $distance);
                }
                if ($secondesAvant <= 30 * 86400 || ($kmRestants !== null && $kmRestants <= 500)) {
                    $alertes[] = $plan->libelle.' · échéance proche ou dépassée';
                }
            }
            $lignes[] = [
                'vehicule' => $vehicule->libelle,
                'immatriculation' => $vehicule->immatriculation,
                'site' => $vehicule->site,
                'energie' => $vehicule->energie,
                'disponible' => $this->planning->blocage($vehicule, $maintenant, $vehicule->kilometrage, $plans[$vehicule->id] ?? []) === null,
                'utilisation' => round(100 * $donnees['secondes'] / $duree, 1),
                'missions' => $donnees['missions'],
                'kilometres' => $distance,
                'litres' => $litres,
                'consommation' => $consommation,
                'depenses' => round($depenses, 2),
                'coutTotal' => round($cout, 2),
                'coutKm' => $distance > 0 ? round($cout / $distance, 2) : null,
                'reforme' => $reforme?->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y-m-d'),
                'alertes' => implode(' · ', $alertes),
            ];
        }

        return ['vehicules' => $lignes, 'permutations' => $this->permutations($lignes)];
    }

    private function projectionPneus(array $releves, int $kilometrage): ?int
    {
        $dernier = array_pop($releves);
        $precedent = array_pop($releves);
        if ($dernier->usureDixiemesMm === null) {
            return null;
        }
        if ($dernier->usureDixiemesMm <= 20) {
            return 0;
        }
        if ($precedent?->usureDixiemesMm === null || $precedent->usureDixiemesMm <= $dernier->usureDixiemesMm || $precedent->kilometrage >= $dernier->kilometrage) {
            return null;
        }

        $distance = ($dernier->usureDixiemesMm - 20) * ($dernier->kilometrage - $precedent->kilometrage)
            / ($precedent->usureDixiemesMm - $dernier->usureDixiemesMm);

        return max(0, (int) ($distance + $dernier->kilometrage - $kilometrage));
    }

    private function permutations(array $lignes): array
    {
        $resultat = [];
        foreach ($lignes as $sollicite) {
            if (!$sollicite['disponible'] || $sollicite['missions'] < 5) {
                continue;
            }
            foreach ($lignes as $reserve) {
                if (
                    $reserve['disponible']
                    && $reserve['site'] !== $sollicite['site']
                    && $reserve['energie'] === $sollicite['energie']
                    && $reserve['utilisation'] + 20 <= $sollicite['utilisation']
                    && $reserve['missions'] < $sollicite['missions']
                ) {
                    $resultat[] = sprintf(
                        '%s (%s, %s %%) ↔ %s (%s, %s %%)',
                        $sollicite['immatriculation'], $sollicite['site'], $sollicite['utilisation'],
                        $reserve['immatriculation'], $reserve['site'], $reserve['utilisation'],
                    );
                    break;
                }
            }
        }

        return $resultat;
    }
}
