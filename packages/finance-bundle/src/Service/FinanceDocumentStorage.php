<?php

namespace FinanceBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Stockage des demandes d'achat signées, sur disque, hors webroot (paramètre finance.documents_dir).
 * PDF uniquement, type vérifié sur le contenu du fichier (jamais sur son nom ni sur le type annoncé par le client).
 * Le chemin enregistré en base est relatif à ce dossier et ne sort jamais du client : on ne peut pas le choisir.
 */
class FinanceDocumentStorage
{
    public const TAILLE_MAX = 10 * 1024 * 1024;
    public const MIME = 'application/pdf';

    /** Anciens dépôts (« uploads/finance/… » sous public/) : relus depuis ce dossier, par leur seul nom de fichier. */
    private const ANCIEN_PREFIXE = 'uploads/finance/';

    private readonly Filesystem $filesystem;
    private readonly string $racine;
    private readonly string $ancienneRacine;

    public function __construct(
        #[Autowire('%finance.documents_dir%')]
        string $documentsDir,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        ?Filesystem $filesystem = null,
    ) {
        $this->racine = rtrim($documentsDir, '/');
        $this->ancienneRacine = rtrim($projectDir, '/').'/public/'.rtrim(self::ANCIEN_PREFIXE, '/');
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /**
     * @return string chemin relatif du fichier enregistré
     *
     * @throws \InvalidArgumentException fichier refusé (message affichable)
     */
    public function enregistrer(UploadedFile $fichier): string
    {
        if (!$fichier->isValid()) {
            throw new \InvalidArgumentException('Le téléversement du fichier a échoué.');
        }
        if ((int) $fichier->getSize() > self::TAILLE_MAX) {
            throw new \InvalidArgumentException(sprintf('Le document dépasse la taille maximale autorisée (%d Mo).', intdiv(self::TAILLE_MAX, 1024 * 1024)));
        }
        if ($fichier->getMimeType() !== self::MIME) {
            throw new \InvalidArgumentException('Le document doit être un fichier PDF.');
        }

        $chemin = sprintf('%s/%s.pdf', date('Y'), Uuid::v7()->toRfc4122());
        $absolu = $this->cheminAbsolu($chemin);
        $this->filesystem->mkdir(\dirname($absolu), 0o750);
        $fichier->move(\dirname($absolu), basename($absolu));
        $this->filesystem->chmod($absolu, 0o640);

        return $chemin;
    }

    /** Nombre maximal de fichiers déposés en une fois (bons de livraison). */
    public const FICHIERS_MAX = 10;

    /**
     * Enregistre plusieurs PDF d'un coup : tout ou rien (en cas de refus, les fichiers déjà enregistrés sont supprimés).
     *
     * @param iterable<mixed> $fichiers
     *
     * @return list<array{chemin: string, nom: string}>
     *
     * @throws \InvalidArgumentException fichier refusé (message affichable)
     */
    public function enregistrerTous(iterable $fichiers): array
    {
        $liste = [];
        foreach ($fichiers as $fichier) {
            if (!$fichier instanceof UploadedFile) {
                throw new \InvalidArgumentException('Fichier invalide.');
            }
            $liste[] = $fichier;
        }
        if (\count($liste) > self::FICHIERS_MAX) {
            throw new \InvalidArgumentException(sprintf('%d fichiers au plus par dépôt.', self::FICHIERS_MAX));
        }
        $enregistres = [];
        try {
            foreach ($liste as $fichier) {
                $nom = $fichier->getClientOriginalName();
                $enregistres[] = ['chemin' => $this->enregistrer($fichier), 'nom' => $nom];
            }
        } catch (\InvalidArgumentException $e) {
            foreach ($enregistres as $document) {
                $this->supprimer($document['chemin']);
            }
            throw new \InvalidArgumentException(sprintf('%s (%s)', $e->getMessage(), $nom ?? 'fichier'), 0, $e);
        }

        return $enregistres;
    }

    public function supprimer(?string $chemin): void
    {
        $absolu = $this->localiser($chemin);
        if ($absolu !== null) {
            $this->filesystem->remove($absolu);
        }
    }

    /** Fichier existant correspondant au chemin enregistré, ou null (chemin vide, invalide ou fichier absent). */
    public function localiser(?string $chemin): ?string
    {
        if ($chemin === null || $chemin === '') {
            return null;
        }
        if (str_starts_with($chemin, self::ANCIEN_PREFIXE)) {
            $nom = substr($chemin, \strlen(self::ANCIEN_PREFIXE));
            if (!preg_match('/^[A-Za-z0-9._-]+\.pdf$/', $nom) || str_contains($nom, '..')) {
                return null;
            }
            $absolu = $this->ancienneRacine.'/'.$nom;

            return is_file($absolu) ? $absolu : null;
        }

        try {
            $absolu = $this->cheminAbsolu($chemin);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return is_file($absolu) ? $absolu : null;
    }

    private function cheminAbsolu(string $chemin): string
    {
        if (!preg_match('#^\d{4}/[0-9a-f-]{36}\.pdf$#', $chemin)) {
            throw new \InvalidArgumentException('Chemin de document invalide.');
        }

        return $this->racine.'/'.$chemin;
    }
}
