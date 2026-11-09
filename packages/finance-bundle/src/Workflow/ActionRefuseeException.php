<?php

namespace FinanceBundle\Workflow;

/** Action du circuit refusée : interdite à cette personne (403) ou impossible depuis ce statut (409). */
final class ActionRefuseeException extends \RuntimeException
{
    public function __construct(string $message, private readonly bool $droits = true)
    {
        parent::__construct($message);
    }

    /** true : la personne n'a pas le droit ; false : le statut ne le permet pas. */
    public function porteSurLesDroits(): bool
    {
        return $this->droits;
    }
}
