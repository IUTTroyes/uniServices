<?php

namespace UnifolioBundle\Service\Media;

use UnifolioBundle\Enum\TypeMediaEnum;

/** Résultat d'un enregistrement de fichier : à recopier dans l'entité TraceMedia. */
final readonly class StoredMedia
{
    public function __construct(
        /** Chemin relatif au répertoire de stockage, ex. "2026/11/0193….png". */
        public string $chemin,
        public string $nomOriginal,
        public string $mimeType,
        public int $taille,
        /** Type retenu : celui demandé, ou celui déduit du format réel. */
        public TypeMediaEnum $type,
    ) {
    }
}
