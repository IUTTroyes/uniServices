<?php

namespace App\Services\Email;

use App\Entity\Structure\StructureDepartement;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Sandbox\SecurityPolicy;
use Twig\Extension\SandboxExtension;

/**
 * Service central d'envoi d'emails.
 *
 * Usage :
 * ```php
 * $emailService->send(
 *     emailKey: 'questionnaire.invitation',
 *     to: $user->getEmail(),
 *     context: ['user' => $user, 'survey' => $survey],
 *     departement: $departement,
 * );
 * ```
 */
final class EmailService
{
    /**
     * Tags Twig autorisés dans les templates saisis via l'interface d'administration.
     * Restreint volontairement pour la sécurité (sandbox Twig).
     */
    private const SANDBOX_ALLOWED_TAGS        = ['if', 'for', 'set', 'block', 'spaceless'];
    private const SANDBOX_ALLOWED_FILTERS     = ['e', 'escape', 'upper', 'lower', 'date', 'nl2br', 'trim', 'length', 'default', 'raw'];
    private const SANDBOX_ALLOWED_METHODS     = [];
    private const SANDBOX_ALLOWED_PROPERTIES  = [];
    private const SANDBOX_ALLOWED_FUNCTIONS   = ['date'];

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TwigEnvironment $twig,
        private readonly EmailTemplateResolver $resolver,
        private readonly string $defaultFromEmail,
        private readonly string $defaultFromName,
    ) {
    }

    /**
     * Envoie un email.
     *
     * @param string|string[]      $to          Destinataire(s)
     * @param array<string, mixed> $context     Variables disponibles dans le template
     * @param string|null          $replyTo     Adresse de réponse optionnelle
     * @param string               $locale      Locale pour la résolution BDD
     */
    public function send(
        string $emailKey,
        string|array $to,
        array $context = [],
        ?StructureDepartement $departement = null,
        ?string $replyTo = null,
        string $locale = 'fr',
    ): void {
        $resolved = $this->resolver->resolve($emailKey, $departement, $locale);

        // Enrichit le contexte avec les infos du département pour le layout
        $context = $this->enrichContext($context, $departement);

        if ($resolved->isFromTwigFile()) {
            [$subject, $htmlBody, $txtBody] = $this->renderFromTwigFile($resolved->definition, $context);
        } else {
            [$subject, $htmlBody, $txtBody] = $this->renderFromDatabase($resolved->dbTemplate, $context);
        }

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->defaultFromName, $this->defaultFromEmail))
            ->subject($subject)
            ->html($htmlBody);

        if ($txtBody !== null) {
            $email->text($txtBody);
        }

        if ($replyTo !== null) {
            $email->replyTo($replyTo);
        }

        foreach ((array) $to as $address) {
            $email->addTo($address);
        }

        $this->mailer->send($email);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Enrichit le contexte avec les variables de branding du département.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function enrichContext(array $context, ?StructureDepartement $departement): array
    {
        if ($departement !== null) {
            $context['departement_libelle'] = $departement->getLibelle();
            $context['departement_couleur'] = $departement->getCouleur() ?? '#1a3a5c';
            // Ajouter l'URL du logo si disponible
            if ($departement->getLogoName() !== null) {
                $context['departement_logo'] = null; // TODO: générer l'URL absolue via asset()
            }
        }

        return $context;
    }

    /**
     * Rendu depuis un fichier Twig (template par défaut du package).
     *
     * @param array<string, mixed> $context
     * @return array{string, string, string|null}  [subject, htmlBody, txtBody]
     */
    private function renderFromTwigFile(AbstractEmailDefinition $definition, array $context): array
    {
        $htmlBody = $this->twig->render($definition->getHtmlTemplatePath(), $context);

        $txtBody = null;
        if ($definition->getTxtTemplatePath() !== null) {
            try {
                $txtBody = $this->twig->render($definition->getTxtTemplatePath(), $context);
            } catch (\Twig\Error\LoaderError) {
                // Le fichier TXT est optionnel, on ignore l'erreur
            }
        }

        // Rendu du sujet (peut contenir des variables Twig)
        $subject = $this->renderString($definition->getDefaultSubject(), $context);

        return [$subject, $htmlBody, $txtBody];
    }

    /**
     * Rendu depuis un template stocké en base de données (personnalisation département).
     * Utilise un environnement Twig avec sandbox pour la sécurité.
     *
     * @param array<string, mixed> $context
     * @return array{string, string, string|null}  [subject, htmlBody, txtBody]
     */
    private function renderFromDatabase(\App\Entity\Email\EmailTemplate $template, array $context): array
    {
        $subject = $this->renderString($template->getSubject(), $context);

        if ($template->isUseLayout()) {
            // Injecte le corps dans le layout commun
            $wrappedHtml = $this->wrapInLayout($template->getBodyHtml(), $context);
            $htmlBody = $this->renderSandboxed($wrappedHtml, $context);
        } else {
            $htmlBody = $this->renderSandboxed($template->getBodyHtml(), $context);
        }

        // Texte brut
        if ($template->getBodyText() !== null) {
            $txtBody = $this->renderSandboxed($template->getBodyText(), $context);
        } else {
            $txtBody = strip_tags(html_entity_decode($htmlBody));
        }

        return [$subject, $htmlBody, $txtBody];
    }

    /**
     * Injecte le contenu dans le layout HTML commun.
     */
    private function wrapInLayout(string $bodyContent, array $context): string
    {
        return sprintf(
            "{%% extends 'emails/layout.html.twig' %%}{%% block body %%}%s{%% endblock %%}",
            $bodyContent
        );
    }

    /**
     * Rend une chaîne Twig simple (ex: le sujet du mail).
     *
     * @param array<string, mixed> $context
     */
    private function renderString(string $template, array $context): string
    {
        return $this->twig->createTemplate($template)->render($context);
    }

    /**
     * Rend un template Twig depuis une chaîne en mode sandbox (sécurisé pour contenu BDD).
     *
     * @param array<string, mixed> $context
     */
    private function renderSandboxed(string $templateString, array $context): string
    {
        // Crée un environnement Twig temporaire avec ArrayLoader + sandbox
        $arrayLoader = new ArrayLoader(['__email__' => $templateString]);
        $chainLoader = new ChainLoader([$arrayLoader, $this->twig->getLoader()]);

        $sandboxedTwig = new TwigEnvironment($chainLoader);

        // Copie les extensions nécessaires
        foreach ($this->twig->getExtensions() as $extension) {
            if (!$sandboxedTwig->hasExtension(get_class($extension))) {
                try {
                    $sandboxedTwig->addExtension($extension);
                } catch (\LogicException) {
                    // Ignore les conflits d'extensions
                }
            }
        }

        // Ajoute le sandbox
        $policy = new SecurityPolicy(
            self::SANDBOX_ALLOWED_TAGS,
            self::SANDBOX_ALLOWED_FILTERS,
            self::SANDBOX_ALLOWED_METHODS,
            self::SANDBOX_ALLOWED_PROPERTIES,
            self::SANDBOX_ALLOWED_FUNCTIONS,
        );
        $sandboxedTwig->addExtension(new SandboxExtension($policy, sandboxed: true));

        return $sandboxedTwig->render('__email__', $context);
    }
}
