<?php

declare(strict_types=1);

namespace ParcautoBundle\Service;

final readonly class SaisieParcauto
{
    public function __construct(private array $valeurs)
    {
    }

    public function texte(string $champ, int $maximum, bool $facultatif = false): string
    {
        $valeur = $this->valeurs[$champ] ?? '';
        if (!is_string($valeur) || (!$facultatif && trim($valeur) === '') || mb_strlen(trim($valeur)) > $maximum) {
            throw new \InvalidArgumentException("Champ $champ invalide.");
        }

        return trim($valeur);
    }

    public function entier(string $champ, int $minimum = 0, bool $facultatif = false, int $maximum = 2147483647): ?int
    {
        $valeur = $this->valeurs[$champ] ?? null;
        if ($facultatif && ($valeur === null || $valeur === '')) {
            return null;
        }
        if (!is_int($valeur) || $valeur < $minimum || $valeur > $maximum) {
            throw new \InvalidArgumentException("Champ $champ invalide.");
        }

        return $valeur;
    }

    public function details(string $champ): self
    {
        $details = $this->valeurs[$champ] ?? null;
        if (!is_array($details)) {
            throw new \InvalidArgumentException("Renseignez les détails du champ $champ.");
        }

        return new self($details);
    }

    public function contient(string $champ): bool
    {
        return array_key_exists($champ, $this->valeurs);
    }

    public function identifiants(string $champ): array
    {
        $ids = $this->valeurs[$champ] ?? [];
        if (!is_array($ids) || !array_is_list($ids)) {
            throw new \InvalidArgumentException("Liste $champ invalide.");
        }
        foreach ($ids as $id) {
            if (!is_int($id) || $id < 1) {
                throw new \InvalidArgumentException("Identifiant $champ invalide.");
            }
        }

        $ids = array_unique($ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    public function booleen(string $champ): bool
    {
        $valeur = $this->valeurs[$champ] ?? null;
        if (!is_bool($valeur)) {
            throw new \InvalidArgumentException("Champ $champ invalide.");
        }

        return $valeur;
    }

    public function choix(string $champ, array $options): string
    {
        $valeur = $this->texte($champ, 30);
        if (!in_array($valeur, $options, true)) {
            throw new \InvalidArgumentException("Champ $champ invalide.");
        }

        return $valeur;
    }

    public function date(string $champ, bool $facultatif = false): ?\DateTimeImmutable
    {
        $valeur = $this->texte($champ, 30, $facultatif);
        if ($valeur === '' && $facultatif) {
            return null;
        }

        $format = strlen($valeur) === 10 ? '!Y-m-d' : '!Y-m-d\TH:i:sP';
        $zone = new \DateTimeZone('Europe/Paris');
        $date = \DateTimeImmutable::createFromFormat($format, str_replace('Z', '+00:00', $valeur), $zone);
        $erreurs = \DateTimeImmutable::getLastErrors();
        if ($date === false || $erreurs !== false || $date->format('Y') < '1000') {
            throw new \InvalidArgumentException("Date $champ invalide.");
        }

        return $date->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function maintenant(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@'.time()))->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function ajouterMois(\DateTimeImmutable $date, int $mois): \DateTimeImmutable
    {
        $locale = $date->setTimezone(new \DateTimeZone('Europe/Paris'));
        $cible = $locale->modify('first day of this month')->modify("+$mois months");

        return $cible->setDate((int) $cible->format('Y'), (int) $cible->format('m'), min((int) $locale->format('j'), (int) $cible->format('t')))
            ->setTimezone(new \DateTimeZone('UTC'));
    }
}
