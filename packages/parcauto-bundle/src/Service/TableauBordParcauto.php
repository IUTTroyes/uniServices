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
            if ($entree->date >= $debut) {
                $parVehicule[$id]['depenses'] += $entree->montantCentimes ?? 0;
                if ($entree->kilometrage !== null && $entree->reservation?->kilometrageRetour !== null
                    && ($entree->kilometrage < $entree->reservation->kilometrageDepart || $entree->kilometrage > $entree->reservation->kilometrageRetour)) {
                    $parVehicule[$id]['incoherences'][] = sprintf('Kilométrage incohérent · journal n° %d, trajet n° %d', $entree->id, $entree->reservation->id);
                }
            }
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
        $derniers = $this->em->createQueryBuilder()->select('IDENTITY(r.vehicule) AS vehicule', 'MAX(r.departLe) AS depart')
            ->from(Reservation::class, 'r')->where('r.departLe < :fin')->setParameter('fin', $fin)
            ->groupBy('r.vehicule')->getQuery()->getArrayResult();
        $derniers = array_column($derniers, 'depart', 'vehicule');
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
            $alertes = $donnees['incoherences'] ?? [];
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
                'dernierDepart' => isset($derniers[$vehicule->id]) ? (new \DateTimeImmutable($derniers[$vehicule->id], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y') : null,
            ];
        }

        $statuts = $this->em->createQueryBuilder()->select('r.statut', 'COUNT(r.id) AS nombre')->from(Reservation::class, 'r')
            ->where('r.debut >= :debut AND r.debut < :fin')->setParameter('debut', $debut)->setParameter('fin', $fin)
            ->groupBy('r.statut')->getQuery()->getArrayResult();

        return ['vehicules' => $lignes, 'activiteMensuelle' => $this->activiteMensuelle($fin),
            'statuts' => array_column($statuts, 'nombre', 'statut')];
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

    public function preparerDeparts(): array
    {
        $maintenant = SaisieParcauto::maintenant();
        $plans = $this->planning->echeancesParVehicule();
        $departs = [];
        $priorites = [];
        $reservations = $this->em->createQueryBuilder()->select('r', 'v')->from(Reservation::class, 'r')
            ->join('r.vehicule', 'v')->where('r.statut = :validee AND r.fin > :maintenant')
            ->setParameter('validee', Reservation::VALIDEE)->setParameter('maintenant', $maintenant)
            ->orderBy('r.debut', 'ASC')->addOrderBy('r.id', 'ASC')->getQuery()->getResult();
        foreach ($reservations as $r) {
            $v = $r->vehicule;
            $blocage = $this->planning->blocage($v, $r->fin, $v->kilometrage, $plans[$v->id] ?? []);
            $ligne = ['id' => $r->id, 'vehicule' => $v->id, 'debut' => $r->debut->format(DATE_ATOM),
                'conducteur' => $r->nomConducteur, 'blocage' => $blocage];
            if (count($departs) < 5) {
                $departs[] = $ligne;
            }
            if ($blocage !== null) {
                $priorites[] = $ligne;
            }
        }

        $entretiens = [];
        foreach ($plans as $echeances) {
            foreach ($echeances as $plan) {
                $v = $plan->vehicule;
                if (!$v->actif) {
                    continue;
                }
                $entretiens[] = ['id' => $plan->id, 'vehicule' => $v->id, 'libelle' => $plan->libelle,
                    'date' => $plan->echeance->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y-m-d'),
                    'km' => $plan->echeanceKm, 'depassee' => $plan->estDepassee($maintenant, $v->kilometrage)];
            }
        }
        usort($entretiens, static fn (array $a, array $b) =>
            ($b['depassee'] <=> $a['depassee']) ?: ($a['date'] <=> $b['date']) ?: ($a['id'] <=> $b['id']));

        return compact('departs', 'priorites', 'entretiens');
    }

    private function activiteMensuelle(\DateTimeImmutable $fin): array
    {
        $zone = new \DateTimeZone('Europe/Paris');
        $debut = $fin->setTimezone($zone)->modify('-1 second')->modify('first day of this month')->setTime(0, 0)->modify('-11 months');
        $mois = [];
        for ($i = 0; $i < 12; ++$i) {
            $cle = $debut->modify("+$i months")->format('Y-m');
            $mois[$cle] = ['mois' => $cle, 'classiques' => 0, 'urgences' => 0];
        }
        $departs = $this->em->createQueryBuilder()->select('r.departLe', 'r.urgence')->from(Reservation::class, 'r')
            ->where('r.departLe >= :debut AND r.departLe < :fin')
            ->setParameter('debut', $debut->setTimezone(new \DateTimeZone('UTC')))->setParameter('fin', $fin)
            ->getQuery()->getArrayResult();
        foreach ($departs as $r) {
            $cle = $r['departLe']->setTimezone($zone)->format('Y-m');
            ++$mois[$cle][$r['urgence'] ? 'urgences' : 'classiques'];
        }

        return array_values($mois);
    }
}
