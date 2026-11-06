<?php

namespace UnifolioBundle\Service\Media;

/** Résultat d'un enregistrement de fichier : à recopier dans l'entité TraceMedia. */
final readonly class StoredMedia
{
    public function __construct(
        /** Chemin relatif au répertoire de stockage, ex. "2026/11/0193….png". */
        public string $chemin,
        public string $nomOriginal,
        public string $mimeType,
        public int $taille,
    ) {
    }
}
