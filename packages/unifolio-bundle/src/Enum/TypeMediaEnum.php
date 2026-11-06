<?php

namespace UnifolioBundle\Enum;

enum TypeMediaEnum: string
{
    case IMAGE = 'image';
    case PDF = 'pdf';
    case VIDEO = 'video';
    case LIEN = 'lien';
    case FICHIER = 'fichier';
    case TEXTE = 'texte';
    case CODE = 'code';
    case EMBED = 'embed';

    public function getLibelle(): string
    {
        return match ($this) {
            self::IMAGE => 'Image',
            self::PDF => 'Document PDF',
            self::VIDEO => 'Vidéo',
            self::LIEN => 'Lien',
            self::FICHIER => 'Fichier',
            self::TEXTE => 'Texte',
            self::CODE => 'Extrait de code',
            self::EMBED => 'Contenu intégré',
        };
    }

    public function getIcone(): string
    {
        return match ($this) {
            self::IMAGE => 'pi pi-image',
            self::PDF => 'pi pi-file-pdf',
            self::VIDEO => 'pi pi-video',
            self::LIEN => 'pi pi-link',
            self::FICHIER => 'pi pi-file',
            self::TEXTE => 'pi pi-align-left',
            self::CODE => 'pi pi-code',
            self::EMBED => 'pi pi-external-link',
        };
    }

    /** Le média repose sur un fichier téléversé (et non une URL ou du texte). */
    public function requiertFichier(): bool
    {
        return \in_array($this, [self::IMAGE, self::PDF, self::VIDEO, self::FICHIER], true);
    }

    /**
     * Types MIME acceptés pour les médias à fichier. Tableau vide = pas de fichier.
     *
     * @return string[]
     */
    public function mimeTypesAcceptes(): array
    {
        return match ($this) {
            self::IMAGE => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            self::PDF => ['application/pdf'],
            self::VIDEO => ['video/mp4', 'video/webm', 'video/quicktime'],
            self::FICHIER => [
                'application/zip',
                'application/pdf',
                'text/plain',
                'text/csv',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.oasis.opendocument.text',
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.oasis.opendocument.presentation',
            ],
            default => [],
        };
    }
}
