<?php

namespace QuestionnaireBundle\State;

use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/** Contrôle des clés envoyées par le client (corps JSON) : refus explicite (422) des champs que le serveur écrit seul. */
final class ChampsEnvoyes
{
    /** @return list<string> clés de premier niveau du corps JSON (hors métadonnées JSON-LD et identifiant technique) */
    public static function cles(?Request $request): array
    {
        if ($request === null) {
            return [];
        }
        try {
            $corps = $request->toArray();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter(array_map('strval', array_keys($corps)), static fn (string $c) => !str_starts_with($c, '@') && $c !== 'id'));
    }

    /**
     * @param list<string> $interdits
     */
    public static function refuser(?Request $request, array $interdits, object $cible): void
    {
        $refuses = array_values(array_intersect(self::cles($request), $interdits));
        if ($refuses === []) {
            return;
        }
        $violations = new ConstraintViolationList();
        foreach ($refuses as $champ) {
            $message = sprintf('Le champ « %s » est géré par le serveur et ne peut pas être envoyé.', $champ);
            $violations->add(new ConstraintViolation($message, $message, [], $cible, $champ, null));
        }

        throw new ValidationException($violations);
    }

    public static function violation(object $cible, string $champ, string $message): ValidationException
    {
        return new ValidationException(new ConstraintViolationList([new ConstraintViolation($message, $message, [], $cible, $champ, null)]));
    }
}
