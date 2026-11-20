<?php

declare(strict_types=1);

namespace ParcautoBundle\Service;

use App\Entity\Users\Personnel;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use ParcautoBundle\Entity\EcheanceEntretien;
use ParcautoBundle\Entity\Equipement;
use ParcautoBundle\Entity\JournalVehicule;
use ParcautoBundle\Entity\Vehicule;

final readonly class GestionParcauto
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function vehicule(SaisieParcauto $saisie, ?Vehicule $vehicule): Vehicule
    {
        $libelle = $saisie->texte('libelle', 150);
        $site = $saisie->texte('site', 120);
        $energie = $saisie->choix('energie', ['essence', 'diesel', 'hybride', 'electrique', 'autre']);
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
            if ($actif && !$vehicule->actif) {
                throw new \DomainException('Enregistrez une remise en service après contrôle.');
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
        $type = $plan !== null ? $plan->type : $saisie->choix('type', ['revision', 'controle_technique', 'pneus', 'entretien']);
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
            $this->em->persist(new JournalVehicule($vehicule, $auteur, JournalVehicule::ENTRETIEN, $date, $libelle, $vehicule->kilometrage, prestataire: $prestataire));
        }

        return $plan;
    }

    public function journal(Vehicule $vehicule, Personnel $auteur, SaisieParcauto $saisie): JournalVehicule
    {
        $type = $saisie->choix('type', [
            JournalVehicule::CARBURANT,
            JournalVehicule::FACTURE,
            JournalVehicule::PNEUS,
            JournalVehicule::REMPLACEMENT_PNEUS,
            JournalVehicule::INCIDENT,
            JournalVehicule::REMISE_SERVICE,
        ]);
        $date = $saisie->date('date');
        if ($date > SaisieParcauto::maintenant()) {
            throw new \InvalidArgumentException('La date doit être passée.');
        }
        $prestataire = $this->equipement($saisie->entier('prestataire', 1, true), Equipement::PRESTATAIRE);
        $libelle = $saisie->texte('libelle', 255);
        $kilometrage = null;
        $montant = null;
        $volume = null;
        $carte = null;
        $usure = null;

        switch ($type) {
            case JournalVehicule::CARBURANT:
                $kilometrage = $saisie->entier('kilometrage');
                $volume = $saisie->entier('volumeMillilitres', 1);
                $montant = $saisie->entier('montantCentimes');
                $carte = $this->equipement($saisie->entier('carte', 1, true), Equipement::CARTE);
                if ($carte?->vehicule !== null && $carte->vehicule->id !== $vehicule->id) {
                    throw new \DomainException('Cette carte carburant est affectée à un autre véhicule.');
                }
                if ($kilometrage > $vehicule->kilometrage) {
                    $vehicule->releverKilometrage($kilometrage);
                }
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
            case JournalVehicule::REMISE_SERVICE:
                if ($vehicule->actif || $prestataire === null) {
                    throw new \DomainException('La remise en service exige un véhicule à l’arrêt et un centre de contrôle enregistré.');
                }
                $vehicule->modifier($vehicule->libelle, $vehicule->site, $vehicule->energie, true);
                break;
        }

        $entree = new JournalVehicule($vehicule, $auteur, $type, $date, $libelle, $kilometrage, $montant, $volume, $prestataire, $carte, $usure);
        $this->em->persist($entree);

        return $entree;
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
