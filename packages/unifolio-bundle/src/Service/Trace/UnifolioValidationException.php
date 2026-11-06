<?php

namespace UnifolioBundle\Service\Trace;

/** Erreur métier présentable à l'utilisateur (réponse HTTP 422). */
final class UnifolioValidationException extends \RuntimeException
{
    /** @param string[] $erreurs */
    public function __construct(private readonly array $erreurs)
    {
        parent::__construct(implode(' ', $erreurs));
    }

    public static function un(string $message): self
    {
        return new self([$message]);
    }

    /** @return string[] */
    public function getErreurs(): array
    {
        return $this->erreurs;
    }
}
