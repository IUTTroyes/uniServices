<?php

declare(strict_types=1);

namespace App\Service\Calendrier;

use App\Entity\Structure\StructureAnneeUniversitaire;

final class CalendrierUniversitaireService
{
    private function date(\DateTimeInterface $date): \DateTimeImmutable
    {
        // Une date civile, sans dépendance aux heures ou changements d’heure.
        return new \DateTimeImmutable($date->format('Y-m-d'), new \DateTimeZone('UTC'));
    }

    public function getDateFin(StructureAnneeUniversitaire $annee): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('%d-08-31', $annee->getAnnee() + 1), new \DateTimeZone('UTC'));
    }

    private function debut(StructureAnneeUniversitaire $annee): \DateTimeImmutable
    {
        $date = $annee->getDateDebutSemaineFormation();
        if ($date === null) throw new \DomainException('La date de début de la semaine de formation n°1 n’est pas renseignée.');
        $date = $this->date($date);
        if ($date->format('N') !== '1' || $date > $this->getDateFin($annee)) {
            throw new \DomainException('La date de début du calendrier universitaire est invalide.');
        }
        return $date;
    }

    public function getSemaineFormation(StructureAnneeUniversitaire $annee, \DateTimeInterface $date): ?int
    {
        $debut = $this->debut($annee);
        $date = $this->date($date);
        if ($date < $debut || $date > $this->getDateFin($annee)) return null;
        return intdiv((int) $debut->diff($date)->days, 7) + 1;
    }

    public function getSemaine(StructureAnneeUniversitaire $annee, int $numero): ?array
    {
        $debut = $this->debut($annee);
        if ($numero < 1) return null;
        $lundi = $debut->modify('+'.(($numero - 1) * 7).' days');
        $finAnnee = $this->getDateFin($annee);
        if ($lundi > $finAnnee) return null;
        $dimanche = $lundi->modify('+6 days');
        return [
            'semaineFormation' => $numero,
            'semaineReelle' => (int) $lundi->format('W'),
            'anneeIso' => (int) $lundi->format('o'),
            'dateLundi' => $lundi->format('Y-m-d'),
            'dateFin' => min($dimanche, $finAnnee)->format('Y-m-d'),
        ];
    }

    /** Vérifie un calendrier historique continu avant d’en déduire son lundi de référence. */
    public function getDateDebutDepuisCorrespondances(array $lignes): ?\DateTimeImmutable
    {
        $reference = null;
        $numeros = [];
        foreach ($lignes as $ligne) {
            $numero = (int) $ligne['semaine_formation'];
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $ligne['date_lundi'], new \DateTimeZone('UTC'));
            if ($numero < 1 || $date === false || $date->format('Y-m-d') !== $ligne['date_lundi']
                || $date->format('N') !== '1' || isset($numeros[$numero])
                || (int) $date->format('W') !== (int) $ligne['semaine_reelle']) {
                throw new \DomainException('Correspondance historique invalide ou dupliquée : vérifier le calendrier avant migration.');
            }
            $numeros[$numero] = true;
            $candidate = $date->modify('-'.(($numero - 1) * 7).' days');
            if ($reference !== null && $reference != $candidate) {
                throw new \DomainException('Le calendrier historique ne suit pas un compteur continu.');
            }
            $reference = $candidate;
        }
        return $reference;
    }

    public function getCalendrier(StructureAnneeUniversitaire $annee): array
    {
        $semaines = [];
        for ($numero = 1; ($semaine = $this->getSemaine($annee, $numero)) !== null; ++$numero) {
            $semaines[] = $semaine;
        }
        return $semaines;
    }
}
