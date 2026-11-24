<?php

declare(strict_types=1);

namespace ParcautoBundle\Service;

use App\Entity\Users\Personnel;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use ParcautoBundle\Entity\EcheanceEntretien;
use ParcautoBundle\Entity\Equipement;
use ParcautoBundle\Entity\JournalVehicule;
use ParcautoBundle\Entity\Reservation;
use ParcautoBundle\Entity\Vehicule;
use ParcautoBundle\Entity\ValeurReferentiel;

final readonly class GestionParcauto
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function codeReferentiel(string $groupe, string $code): string
    {
        $valeur = $this->em->find(ValeurReferentiel::class, ['groupe' => $groupe, 'code' => $code], LockMode::PESSIMISTIC_READ);
        if ($valeur === null) {
            throw new \InvalidArgumentException('Valeur inconnue dans le référentiel.');
        }

        return $valeur->code;
    }

    public function vehicule(SaisieParcauto $saisie, ?Vehicule $vehicule): Vehicule
    {
        $libelle = $saisie->texte('libelle', 150);
        $site = $saisie->texte('site', 120);
        $energie = $this->codeReferentiel('energie', $saisie->texte('energie', 30));
        $actif = $saisie->booleen('actif');
        if ($vehicule === null) {
            $vehicule = new Vehicule(
                mb_strtoupper($saisie->texte('immatriculation', 24)),
                $libelle,
                $site,
                $energie,
                $saisie->entier('kilometrage'),
                $actif,
            );
            $this->em->persist($vehicule);
        } else {
            if ($actif !== $vehicule->actif) {
                throw new \DomainException('Modifiez la disponibilité depuis le journal en indiquant le motif.');
            }
            $vehicule->modifier($libelle, $site, $energie, $actif);
        }

        $vehicule->misEnServiceLe = $saisie->date('misEnServiceLe', true);
        $vehicule->reformePrevueLe = $saisie->date('reformePrevueLe', true);
        if ($vehicule->misEnServiceLe !== null && $vehicule->reformePrevueLe !== null && $vehicule->reformePrevueLe <= $vehicule->misEnServiceLe) {
            throw new \InvalidArgumentException('La réforme doit suivre la mise en service.');
        }
        $vehicule->reformeKm = $saisie->entier('reformeKm', 1, true);
        $vehicule->achatCentimes = $saisie->entier('achatCentimes', 0, true) ?? 0;
        $vehicule->consommationReference = $saisie->entier('consommationReference', 1, true, 1000);

        return $vehicule;
    }

    public function echeance(Vehicule $vehicule, Personnel $auteur, SaisieParcauto $saisie, ?EcheanceEntretien $plan): EcheanceEntretien
    {
        $type = $plan !== null ? $plan->type : $this->codeReferentiel('plan', $saisie->texte('type', 30));
        $libelle = $plan !== null ? $plan->libelle : $saisie->texte('libelle', 150);
        $date = $saisie->date('effectueLe', true);
        $maintenant = SaisieParcauto::maintenant();
        $mois = $type === 'pneus' ? 3 : $saisie->entier('intervalleMois', 1, true, 120);
        $intervalleKm = $saisie->entier('intervalleKm', 1, true, 1000000);
        $echeance = $saisie->date('echeance', true);
        $echeanceKm = $saisie->entier('echeanceKm', 1, true);

        if ($date !== null && ($date > $maintenant || ($plan?->effectueLe !== null && $date <= $plan->effectueLe))) {
            throw new \DomainException('La date du contrôle doit être postérieure au dernier contrôle et déjà passée.');
        }
        if ($type === 'pneus' && $date === null) {
            throw new \InvalidArgumentException('La date du contrôle des pneus est obligatoire.');
        }
        if ($date !== null && $mois !== null) {
            $echeance = SaisieParcauto::ajouterMois($date, $mois);
        }
        if ($date !== null && $intervalleKm !== null) {
            $echeanceKm = $vehicule->kilometrage + $intervalleKm;
        }
        if ($echeance === null || ($date !== null && $echeance <= $date)) {
            throw new \InvalidArgumentException('Renseignez une échéance valide.');
        }
        if ($plan !== null && $date === null) {
            throw new \InvalidArgumentException('Renseignez la date de l’intervention.');
        }

        if ($plan === null) {
            $plan = new EcheanceEntretien($vehicule, $type, $libelle, $echeance, $echeanceKm);
            $this->em->persist($plan);
        }
        $plan->intervalleMois = $mois;
        $plan->intervalleKm = $intervalleKm;

        if ($date !== null) {
            $plan->renouveler($date, $echeance, $echeanceKm);
            $prestataire = $this->equipement($saisie->entier('prestataire', 1, true), Equipement::PRESTATAIRE);
            $this->em->persist(new JournalVehicule($vehicule, $auteur, JournalVehicule::ENTRETIEN, $date, $libelle, $vehicule->kilometrage, prestataire: $prestataire, reservation: $this->trajetIntervention($vehicule, $saisie, $date)));
        }

        return $plan;
    }

    public function journal(Vehicule $vehicule, Personnel $auteur, SaisieParcauto $saisie, ?Reservation $mission = null): JournalVehicule
    {
        if ($mission !== null && $mission->statut !== Reservation::EN_COURS) {
            throw new \DomainException('La mission doit être en cours pour ajouter une déclaration.');
        }
        $type = $mission === null
            ? $this->codeReferentiel('journal', $saisie->texte('type', 25))
            : $saisie->choix('type', [JournalVehicule::CARBURANT, JournalVehicule::INCIDENT, JournalVehicule::TELEPEAGE]);
        $date = $mission !== null ? SaisieParcauto::maintenant() : $saisie->date('date');
        if ($date > SaisieParcauto::maintenant()) {
            throw new \InvalidArgumentException('La date doit être passée.');
        }
        $prestataire = $mission === null ? $this->equipement($saisie->entier('prestataire', 1, true), Equipement::PRESTATAIRE) : null;
        $libelle = $saisie->texte('libelle', 255);
        $kilometrage = null;
        $montant = null;
        $volume = null;
        $carte = null;
        $usure = null;
        $pass = null;
        $numeroFacture = $saisie->texte('numeroFacture', 120, true);


        switch ($type) {
            case JournalVehicule::CARBURANT:
                $kilometrage = $saisie->entier('kilometrage', $mission?->kilometrageDepart ?? 0);
                $volume = $saisie->entier('volumeMillilitres', 1);
                $montant = $saisie->entier('montantCentimes');
                $carte = $mission === null ? $this->equipement($saisie->entier('carte', 1, true), Equipement::CARTE) : null;
                if ($carte?->vehicule !== null && $carte->vehicule->id !== $vehicule->id) {
                    throw new \DomainException('Cette carte carburant est affectée à un autre véhicule.');
                }
                if ($kilometrage > $vehicule->kilometrage) {
                    $vehicule->releverKilometrage($kilometrage);
                }
                break;
            case JournalVehicule::TELEPEAGE:
                $pass = $this->equipement($saisie->entier('pass', 1, true), Equipement::PASS);
                if ($pass?->vehicule !== null && $pass->vehicule->id !== $vehicule->id) {
                    throw new \DomainException('Ce pass est affecté à un autre véhicule.');
                }
                $montant = $saisie->entier('montantCentimes', 0, true);
                break;
            case JournalVehicule::FACTURE:
                if ($prestataire === null) {
                    throw new \InvalidArgumentException('Sélectionnez le prestataire de la facture.');
                }
                $montant = $saisie->entier('montantCentimes');
                break;
            case JournalVehicule::PNEUS:
                $kilometrage = $vehicule->kilometrage;
                $usure = $saisie->entier('usureDixiemesMm', 0, false, 300);
                break;
            case JournalVehicule::INCIDENT:
            case JournalVehicule::REMPLACEMENT_PNEUS:
                $kilometrage = $vehicule->kilometrage;
                break;
            case JournalVehicule::HORS_SERVICE:
            case JournalVehicule::REMISE_SERVICE:
                $enService = $type === JournalVehicule::REMISE_SERVICE;
                if ($vehicule->actif === $enService) {
                    throw new \DomainException('Le véhicule est déjà dans cet état.');
                }
                $vehicule->modifier($vehicule->libelle, $vehicule->site, $vehicule->energie, $enService);
                break;
        }

        $entree = new JournalVehicule($vehicule, $auteur, $type, $date, $libelle, $kilometrage, $montant, $volume, $prestataire, $carte, $usure,
            $mission ?? $this->trajetIntervention($vehicule, $saisie, $date), $pass, $numeroFacture === '' ? null : $numeroFacture);
        $this->em->persist($entree);

        return $entree;
    }

    private function trajetIntervention(Vehicule $vehicule, SaisieParcauto $saisie, \DateTimeImmutable $date): ?Reservation
    {
        // La valeur zéro représente une intervention réalisée sur place.
        $id = $saisie->entier('trajet');
        if ($id === 0) {
            return null;
        }

        $trajet = $this->em->getRepository(Reservation::class)->findOneBy(['id' => $id, 'vehicule' => $vehicule]);
        $zone = new \DateTimeZone('Europe/Paris');
        $jour = $date->setTimezone($zone)->format('Y-m-d');
        if ($trajet?->departLe === null || $jour < $trajet->departLe->setTimezone($zone)->format('Y-m-d')
            || $jour > ($trajet->retourLe ?? SaisieParcauto::maintenant())->setTimezone($zone)->format('Y-m-d')) {
            throw new \InvalidArgumentException('Choisissez un trajet de ce véhicule couvrant la date de l’intervention.');
        }

        return $trajet;
    }

    public function equipement(?int $id, string $type): ?Equipement
    {
        if ($id === null) {
            return null;
        }

        $equipement = $this->em->find(Equipement::class, $id, LockMode::PESSIMISTIC_WRITE);
        if ($equipement === null || $equipement->type !== $type || !$equipement->actif) {
            throw new \InvalidArgumentException('Équipement ou prestataire indisponible.');
        }

        return $equipement;
    }
}
