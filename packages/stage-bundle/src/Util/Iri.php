<?php

namespace StageBundle\Util;

/** Identifiants reçus du front : entier, chaîne numérique, IRI (« /api/x/12 ») ou entité. */
final class Iri
{
    /** Identifiant strictement positif, ou null si la valeur n'en contient pas. */
    public static function id(mixed $valeur): ?int
    {
        if (\is_object($valeur) && method_exists($valeur, 'getId')) {
            $valeur = $valeur->getId();
        }
        if (\is_string($valeur) && str_contains($valeur, '/')) {
            $valeur = basename($valeur);
        }
        if (\is_bool($valeur) || !is_numeric($valeur)) {
            return null;
        }
        $id = (int) $valeur;

        return $id > 0 && (string) $id === (string) $valeur ? $id : null;
    }
}
