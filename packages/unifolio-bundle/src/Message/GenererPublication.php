<?php

namespace UnifolioBundle\Message;

/** Demande de génération statique d'une publication (traitée par GenererPublicationHandler). */
final class GenererPublication
{
    public function __construct(public readonly string $publicationUuid)
    {
    }
}
