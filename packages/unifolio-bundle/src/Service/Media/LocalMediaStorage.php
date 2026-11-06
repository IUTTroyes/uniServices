<?php

namespace UnifolioBundle\Service\Media;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Enum\TypeMediaEnum;

/** Stockage sur disque local, hors webroot (paramètre unifolio.media_dir). */
class LocalMediaStorage implements MediaStorageInterface
{
    private readonly Filesystem $filesystem;
    private readonly string $racine;

    public function __construct(
        string $unifolioMediaDir,
        private readonly int $unifolioMediaMaxSize,
        ?Filesystem $filesystem = null,
    ) {
        $this->racine = rtrim($unifolioMediaDir, '/');
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function store(UploadedFile $fichier, TypeMediaEnum $type): StoredMedia
    {
        if (!$type->requiertFichier()) {
            throw new MediaValidationException(sprintf('Le type « %s » ne repose pas sur un fichier.', $type->getLibelle()));
        }
        if (!$fichier->isValid()) {
            throw new MediaValidationException('Le téléversement du fichier a échoué.');
        }

        $taille = (int) $fichier->getSize();
        if ($taille > $this->unifolioMediaMaxSize) {
            throw new MediaValidationException(sprintf(
                'Le fichier dépasse la taille maximale autorisée (%d Mo).',
                intdiv($this->unifolioMediaMaxSize, 1024 * 1024)
            ));
        }

        // Type détecté depuis le contenu, jamais depuis le nom ou le type déclaré par le client.
        $mimeType = (string) $fichier->getMimeType();
        if (!\in_array($mimeType, $type->mimeTypesAcceptes(), true)) {
            throw new MediaValidationException(sprintf(
                'Le format « %s » n\'est pas accepté pour un média de type « %s ».',
                $mimeType,
                $type->getLibelle()
            ));
        }

        $extension = $fichier->guessExtension() ?: 'bin';
        $chemin = sprintf('%s/%s.%s', date('Y/m'), Uuid::v7()->toRfc4122(), $extension);

        $this->filesystem->mkdir(\dirname($this->cheminAbsolu($chemin)), 0o750);
        $fichier->move(\dirname($this->cheminAbsolu($chemin)), basename($chemin));

        return new StoredMedia(
            chemin: $chemin,
            nomOriginal: $this->nettoyerNom($fichier->getClientOriginalName()),
            mimeType: $mimeType,
            taille: $taille,
        );
    }

    public function cheminAbsolu(string $chemin): string
    {
        $chemin = ltrim($chemin, '/');
        if ($chemin === '' || str_contains($chemin, '..') || str_contains($chemin, "\0")) {
            throw new \InvalidArgumentException('Chemin de média invalide.');
        }

        return $this->racine . '/' . $chemin;
    }

    public function exists(string $chemin): bool
    {
        return $this->filesystem->exists($this->cheminAbsolu($chemin));
    }

    public function delete(string $chemin): void
    {
        $this->filesystem->remove($this->cheminAbsolu($chemin));
    }

    private function nettoyerNom(string $nom): string
    {
        $nom = basename(str_replace('\\', '/', $nom));
        $nom = preg_replace('/[\x00-\x1F\x7F]/u', '', $nom) ?? '';

        return mb_substr($nom, 0, 255) ?: 'fichier';
    }
}
