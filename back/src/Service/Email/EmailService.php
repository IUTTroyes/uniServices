<?php

namespace App\Service\Email;

use App\Entity\Structure\StructureDepartement;
use App\Repository\EtablissementRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
        /** URL publique du front (« https://… ») : les liens relatifs des e-mails (« /app/… ») sont rendus absolus. */
        private readonly string $urlFrontend = '',
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly ?EtablissementRepository $etablissements = null,
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
        array $attachments = [],
    ): void {
        $resolved = $this->resolver->resolve($emailKey, $departement, $locale);

        // Enrichit le contexte avec les infos du département et l'habillage imposé de l'établissement (layout)
        $context = $this->enrichContext($context, $departement);
        $context = $this->habiller($context, $resolved->definition?->getCategorie());
        $habillage = $context['habillage'];

        if ($resolved->isFromTwigFile()) {
            [$subject, $htmlBody, $txtBody] = $this->renderFromTwigFile($resolved->definition, $context);
        } else {
            try {
                [$subject, $htmlBody, $txtBody] = $this->renderFromDatabase($resolved->dbTemplate, $context);
            } catch (\Throwable $e) {
                // Une personnalisation invalide ne doit jamais empêcher l'envoi : on reprend le modèle du package.
                if ($resolved->definition === null) {
                    throw $e;
                }
                $this->logger->error('E-mail personnalisé invalide, modèle par défaut utilisé.', ['email' => $emailKey, 'erreur' => $e->getMessage()]);
                [$subject, $htmlBody, $txtBody] = $this->renderFromTwigFile($resolved->definition, $context);
            }
        }
        $htmlBody = $this->liensAbsolus($htmlBody);

        $email = (new Email())
            ->from(sprintf('%s <%s>', $habillage['expediteur_nom'] ?: $this->defaultFromName, $this->defaultFromEmail))
            ->subject($subject)
            ->html($htmlBody);
        $replyTo ??= $habillage['reply_to'] ?: null;

        if ($txtBody !== null) {
            $email->text($txtBody);
        }

        if ($replyTo !== null) {
            $email->replyTo($replyTo);
        }

        foreach ((array) $to as $address) {
            $email->addTo($address);
        }

        foreach ($attachments as $att) {
            if (is_array($att) && isset($att['path'])) {
                $email->attachFromPath($att['path'], $att['name'] ?? null, $att['contentType'] ?? null);
            } elseif (is_string($att) && file_exists($att)) {
                $email->attachFromPath($att);
            }
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
            if ($departement->getCouleur() !== null) {
                $context['departement_couleur'] = $departement->getCouleur();
            }
        }

        return $context;
    }

    /**
     * Habillage imposé par le layout (en-tête, signature, pied de page légal), à partir des réglages de l'établissement.
     * Écrase toute valeur du même nom passée par un package : ni un package ni un modèle personnalisé ne peuvent
     * retirer les mentions. Le lien vers les préférences n'est mis que pour les notifications.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $surcharge réglages non enregistrés (aperçu de l'écran d'habillage)
     *
     * @return array<string, mixed>
     */
    public function habiller(array $context, ?string $categorie, array $surcharge = []): array
    {
        $etab = $this->etablissements?->findPrincipal();
        $reglages = array_merge(\App\Entity\Etablissement::HABILLAGE_EMAILS_DEFAUT, $etab?->getHabillageEmails() ?? [], $surcharge);
        $etablissement = $etab?->getLibelle() ?: 'UniServices';

        $context['habillage'] = [
            'etablissement' => $etablissement,
            'universite' => $etab?->getUniversite()->getLibelle() ?? '',
            'logo_url' => $etab?->getLogoName() ? '/uploads/etablissement/'.$etab->getLogoName() : '',
            'expediteur' => $reglages['expediteur_nom'] ?: $etablissement,
            'expediteur_nom' => $reglages['expediteur_nom'] ?? '',
            'reply_to' => $reglages['reply_to'] ?? '',
            'signature' => (string) $reglages['signature'],
            'mentions_legales' => (string) $reglages['mentions_legales'],
            'contact_rgpd' => $reglages['contact_rgpd'] ?? '',
            'lien_preferences' => $categorie === AbstractEmailDefinition::CATEGORIE_NOTIFICATION ? '/app/auth/notifications' : '',
        ];
        $context['couleur_principale'] = $context['departement_couleur'] ?? $reglages['couleur'];

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
        $subject = self::texteBrut($this->renderString($definition->getDefaultSubject(), $context));

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
        // Le layout est toujours imposé (mentions légales, préférences) : EmailTemplate::$useLayout est ignoré.
        [$subject, $htmlBody] = $this->rendrePersonnalisation($template->getSubject(), $template->getBodyHtml(), $context);

        // Texte brut
        if ($template->getBodyText() !== null) {
            $txtBody = $this->renderSandboxed($template->getBodyText(), $context);
        } else {
            $txtBody = strip_tags(html_entity_decode($htmlBody));
        }

        return [$subject, $htmlBody, $txtBody];
    }

    /**
     * Rend un objet et un corps saisis dans l'éditeur (sandbox : contenu d'administration, jamais du code).
     * Sert à l'envoi, à la vérification avant enregistrement et à l'aperçu.
     *
     * @param array<string, mixed> $context
     *
     * @return array{string, string} [objet, corps HTML]
     *
     * @throws \Twig\Error\Error si le modèle est invalide ou utilise une construction non autorisée
     */
    public function rendrePersonnalisation(string $subject, string $bodyHtml, array $context, ?string $categorie = null): array
    {
        if (!isset($context['habillage'])) {
            $context = $this->habiller($context, $categorie);
        }
        $objet = self::texteBrut($this->renderSandboxed($subject, $context));
        $corps = $this->renderSandboxed($this->wrapInLayout($bodyHtml, $context), $context);

        return [$objet, $this->liensAbsolus($corps)];
    }

    /** Envoie un e-mail déjà rendu (envoi de test depuis l'éditeur). */
    public function sendRendered(string $to, string $subject, string $htmlBody): void
    {
        $this->mailer->send((new Email())
            ->from(sprintf('%s <%s>', $this->defaultFromName, $this->defaultFromEmail))
            ->to($to)
            ->subject($subject)
            ->html($htmlBody)
            ->text(strip_tags(html_entity_decode($htmlBody))));
    }

    /**
     * Corps par défaut d'un e-mail (contenu du bloc « body » de son gabarit Twig), point de départ de l'éditeur.
     * Null si le gabarit n'a pas de bloc « body » lisible.
     */
    public function corpsParDefaut(AbstractEmailDefinition $definition): ?string
    {
        try {
            $source = $this->twig->getLoader()->getSourceContext($definition->getHtmlTemplatePath())->getCode();
        } catch (\Throwable) {
            return null;
        }
        if (!preg_match('/\{%-?\s*block\s+body\s*-?%\}(.*?)\{%-?\s*endblock(?:\s+body)?\s*-?%\}/s', $source, $m)) {
            return null;
        }
        $lignes = explode("\n", trim($m[1], "\n"));
        // Retrait commun des lignes non vides, retiré pour présenter un code lisible dans l'éditeur.
        $retrait = min(array_map(static fn ($l) => trim($l) === '' ? \PHP_INT_MAX : \strlen($l) - \strlen(ltrim($l)), $lignes));

        return implode("\n", array_map(static fn ($l) => substr($l, min($retrait, \strlen($l) - \strlen(ltrim($l)))), $lignes));
    }

    /** Objet d'un e-mail : texte brut sur une ligne (l'échappement HTML de Twig n'a pas de sens dans un objet). */
    private static function texteBrut(string $texte): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($texte, \ENT_QUOTES | \ENT_HTML5, 'UTF-8')));
    }

    /** Liens et images relatifs (« /app/… ») rendus absolus : un client de messagerie ne connaît pas l'application. */
    public function liensAbsolus(string $html): string
    {
        $base = rtrim($this->urlFrontend, '/');
        if ($base === '') {
            return $html;
        }

        return (string) preg_replace('#\b(href|src)=(["\'])/(?!/)#i', '$1=$2'.$base.'/', $html);
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
