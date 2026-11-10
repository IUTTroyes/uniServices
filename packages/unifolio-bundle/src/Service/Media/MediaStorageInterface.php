<?php

namespace UnifolioBundle\Service\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use UnifolioBundle\Enum\TypeMediaEnum;

/**
 * Stockage des fichiers des traces. Les fichiers ne sont jamais exposés
 * directement : ils sont servis par un contrôleur après vérification des droits.
 */
interface MediaStorageInterface
{
    /**
     * @param TypeMediaEnum|null $type null = déduit du format réel du fichier
     *
     * @throws MediaValidationException si le format n'est pas accepté
     */
    public function store(UploadedFile $fichier, ?TypeMediaEnum $type = null): StoredMedia;

    /** Chemin absolu sur disque, pour un BinaryFileResponse. */
    public function cheminAbsolu(string $chemin): string;

    public function exists(string $chemin): bool;

    public function delete(string $chemin): void;
}
