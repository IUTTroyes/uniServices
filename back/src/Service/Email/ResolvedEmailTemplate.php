<?php

namespace App\Service\Email;

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

    /** @param AbstractEmailDefinition|null $definition définition de l'e-mail (repli si la personnalisation échoue) */
    public static function fromDatabase(EmailTemplate $template, ?AbstractEmailDefinition $definition = null): self
    {
        return new self(self::SOURCE_DATABASE, $template, $definition);
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
