<?php

namespace App\Services\Email;

use App\Entity\Email\EmailTemplate;

/**
 * Résultat de la résolution d'un template email.
 * Encapsule soit un EmailTemplate BDD, soit une AbstractEmailDefinition (fichier Twig).
 */
final class ResolvedEmailTemplate
{
    public const SOURCE_DATABASE  = 'database';
    public const SOURCE_TWIG_FILE = 'twig_file';

    private function __construct(
        public readonly string $source,
        public readonly ?EmailTemplate $dbTemplate,
        public readonly ?AbstractEmailDefinition $definition,
    ) {
    }

    public static function fromDatabase(EmailTemplate $template): self
    {
        return new self(self::SOURCE_DATABASE, $template, null);
    }

    public static function fromDefinition(AbstractEmailDefinition $definition): self
    {
        return new self(self::SOURCE_TWIG_FILE, null, $definition);
    }

    public function isFromDatabase(): bool
    {
        return $this->source === self::SOURCE_DATABASE;
    }

    public function isFromTwigFile(): bool
    {
        return $this->source === self::SOURCE_TWIG_FILE;
    }
}
