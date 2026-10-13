<?php

declare(strict_types=1);

namespace App\Service\Pdf;

use DateTimeImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use Sensiolabs\GotenbergBundle\Builder\Pdf\HtmlPdfBuilder;
use Sensiolabs\GotenbergBundle\Enumeration\Unit;
use Sensiolabs\GotenbergBundle\GotenbergPdfInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service global de génération de documents PDF via Gotenberg Chromium.
 * Centralise la configuration Gotenberg, les marges, l'en-tête, le pied de page
 * avec mention automatique d'intranet, horodatage et pagination dynamique.
 */
class PdfExportService
{
    public function __construct(
        private readonly GotenbergPdfInterface $gotenberg,
        private readonly ?Security $security = null,
    ) {
    }

    /**
     * Crée et configure le builder Gotenberg Chromium avec en-tête et pied de page standardisés.
     */
    public function createBuilder(
        string $contentTemplate,
        array $context,
        string $filename,
        ?PdfExportOptions $options = null,
    ): HtmlPdfBuilder {
        $options ??= new PdfExportOptions();

        $genDate = $options->generationDate ?? new DateTimeImmutable('now');
        $formattedDate = $this->formatGenerationDate($genDate);
        $generatedBy = $options->generatedBy ?? $this->resolveCurrentUserName();

        $headerContext = array_merge([
            'etablissement' => $options->etablissement,
            'title' => $options->title,
            'subtitle' => $options->subtitle,
            'departement' => $options->departement,
            'anneeUniversitaire' => $options->anneeUniversitaire,
        ], $options->extraContext);

        $footerContext = array_merge([
            'generationMention' => $options->generationMention,
            'generationDate' => $genDate,
            'generationDateFormatted' => $formattedDate,
            'generatedBy' => $generatedBy,
        ], $options->extraContext);

        // Nettoyer le nom de fichier des éventuelles extensions dupliquées
        $cleanFilename = preg_replace('/\.pdf$/i', '', $filename);

        /** @var HtmlPdfBuilder $builder */
        $builder = $this->gotenberg->html()
            ->content($contentTemplate, $context)
            ->landscape($options->landscape)
            ->paperStandardSize($options->paperSize)
            ->printBackground(true)
            ->marginTop($options->marginTop, Unit::Centimeters)
            ->marginBottom($options->marginBottom, Unit::Centimeters)
            ->marginLeft($options->marginLeft, Unit::Centimeters)
            ->marginRight($options->marginRight, Unit::Centimeters)
            ->fileName($cleanFilename, HeaderUtils::DISPOSITION_ATTACHMENT);

        if ($options->withHeader) {
            $builder->header($options->headerTemplate, $headerContext);
        }

        if ($options->withFooter) {
            $builder->footer($options->footerTemplate, $footerContext);
        }

        return $builder;
    }

    /**
     * Génère et diffuse la réponse streamée du PDF vers le client.
     */
    public function generateStreamedResponse(
        string $contentTemplate,
        array $context,
        string $filename,
        ?PdfExportOptions $options = null,
    ): StreamedResponse {
        return $this->createBuilder($contentTemplate, $context, $filename, $options)
            ->generate()
            ->stream();
    }

    /**
     * Formate la date de génération avec la locale française.
     */
    private function formatGenerationDate(DateTimeInterface $date): string
    {
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            'Europe/Paris',
            IntlDateFormatter::GREGORIAN,
            "d MMMM y 'à' HH'h'mm"
        );

        $formatted = $formatter->format($date);
        return $formatted !== false ? $formatted : $date->format('d/m/Y à H:i');
    }

    /**
     * Résout le nom et prénom de l'utilisateur actuellement connecté s'il existe.
     */
    private function resolveCurrentUserName(): ?string
    {
        if ($this->security === null) {
            return null;
        }

        $user = $this->security->getUser();
        if ($user === null) {
            return null;
        }

        if (method_exists($user, 'getPrenom') && method_exists($user, 'getNom')) {
            $prenom = (string) $user->getPrenom();
            $nom = (string) $user->getNom();
            $fullname = trim($prenom . ' ' . $nom);
            return $fullname !== '' ? $fullname : null;
        }

        if (method_exists($user, 'getUserIdentifier')) {
            return $user->getUserIdentifier();
        }

        return null;
    }
}
