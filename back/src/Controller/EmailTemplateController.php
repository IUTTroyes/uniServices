<?php

namespace App\Controller;

use App\Entity\Email\EmailTemplate;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationTypeRegistry;
use App\Repository\EtablissementRepository;
use App\Repository\Email\EmailTemplateRepository;
use App\Repository\Structure\StructureDepartementRepository;
use App\Service\Email\AbstractEmailDefinition;
use App\Service\Email\EmailRegistry;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Éditeur des e-mails de l'application (super administrateur), tous packages confondus :
 * personnalisation au niveau de l'établissement (sans département) ou d'un département, qui prime.
 *
 * Résolution à l'envoi : département > établissement > e-mail parent (ex. modèle générique des notifications) > gabarit
 * du package. Les personnalisations sont rendues dans le sandbox Twig et vérifiées avant d'être enregistrées.
 */
#[Route('/api/email', name: 'api_email_')]
#[IsGranted('SUPER_ADMIN')]
class EmailTemplateController extends AbstractController
{
    public function __construct(
        private readonly EmailRegistry $registry,
        private readonly EmailTemplateRepository $templateRepository,
        private readonly StructureDepartementRepository $departementRepository,
        private readonly EmailService $emails,
        private readonly EntityManagerInterface $em,
        private readonly NotificationTypeRegistry $notifications,
        private readonly EtablissementRepository $etablissements,
    ) {
    }

    /**
     * Toutes les définitions, groupées par application, avec leur état de personnalisation :
     * « personnalise » au niveau consulté, « herite » si une personnalisation plus large (établissement, modèle parent)
     * s'applique.
     *
     * GET /api/email/definitions[?departement={id}]
     */
    #[Route('/definitions', name: 'definitions', methods: ['GET'])]
    public function definitions(Request $request): JsonResponse
    {
        $departement = $this->departement($request->query->getInt('departement', 0));
        $globales = $this->clesPersonnalisees(null);
        $locales = $departement !== null ? $this->clesPersonnalisees($departement) : [];

        $result = [];
        foreach ($this->registry->groupedByBundle() as $bundle => $definitions) {
            usort($definitions, static fn (AbstractEmailDefinition $a, AbstractEmailDefinition $b) => strcmp($a->getLabel(), $b->getLabel()));
            $result[] = [
                'bundle' => $bundle,
                'items' => array_map(fn (AbstractEmailDefinition $def) => [
                    'key' => $def->getKey(),
                    'label' => $def->getLabel(),
                    'description' => $def->getDescription(),
                    'defaultSubject' => $def->getDefaultSubject(),
                    'parentKey' => $def->getParentKey(),
                    'categorie' => $def->getCategorie(),
                    'desactivable' => $this->desactivable($def),
                    'isCustomized' => \in_array($def->getKey(), $departement !== null ? $locales : $globales, true),
                    'personnalisation' => $this->niveau($def, $departement, $globales, $locales),
                ], $definitions),
            ];
        }

        return $this->json($result);
    }

    /**
     * Modèle d'un e-mail au niveau demandé, avec ce qui s'applique aujourd'hui et le modèle par défaut (point de départ).
     *
     * GET /api/email/templates/{key}[?departement={id}]
     */
    #[Route('/templates/{key}', name: 'template_get', methods: ['GET'], requirements: ['key' => '[a-z0-9_.]+'])]
    public function getTemplate(string $key, Request $request): JsonResponse
    {
        $definition = $this->registry->get($key) ?? throw $this->createNotFoundException('Clé d\'email inconnue : '.$key);
        $departement = $this->departement($request->query->getInt('departement', 0));
        $template = $this->templateAuNiveau($key, $departement);

        $globales = $this->clesPersonnalisees(null);
        $locales = $departement !== null ? $this->clesPersonnalisees($departement) : [];

        return $this->json([
            'id' => $template?->getId(),
            'emailKey' => $key,
            'label' => $definition->getLabel(),
            'description' => $definition->getDescription(),
            'bundle' => $definition->getBundle(),
            'departement' => $departement?->getId(),
            'departementLibelle' => $departement?->getLibelle(),
            'subject' => $template?->getSubject() ?? $definition->getDefaultSubject(),
            'bodyHtml' => $template?->getBodyHtml(),
            'defaultSubject' => $definition->getDefaultSubject(),
            'defaultBodyHtml' => $this->emails->corpsParDefaut($definition),
            'isCustomized' => $template !== null,
            'personnalisation' => $this->niveau($definition, $departement, $globales, $locales),
            'parentKey' => $definition->getParentKey(),
            'categorie' => $definition->getCategorie(),
            'desactivable' => $this->desactivable($definition),
            'availableVariables' => $definition->getAvailableVariables(),
        ]);
    }

    /**
     * Crée ou met à jour une personnalisation, après vérification du rendu (variables d'exemple, sandbox).
     *
     * POST /api/email/templates   { emailKey, departement (id|null), subject, bodyHtml }
     */
    #[Route('/templates', name: 'template_save', methods: ['POST'])]
    public function saveTemplate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $key = (string) ($data['emailKey'] ?? '');
        $subject = trim((string) ($data['subject'] ?? ''));
        $bodyHtml = (string) ($data['bodyHtml'] ?? '');
        if ($key === '' || $subject === '' || trim($bodyHtml) === '') {
            return $this->json(['error' => 'L\'objet et le corps du message sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }
        $definition = $this->registry->get($key) ?? throw $this->createNotFoundException('Clé d\'email inconnue : '.$key);
        $departement = null;
        if (!empty($data['departement'])) {
            $departement = $this->departement((int) $data['departement']) ?? throw $this->createNotFoundException('Département introuvable.');
        }

        $erreur = $this->verifier($definition, $subject, $bodyHtml);
        if ($erreur !== null) {
            return $this->json(['error' => $erreur], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $template = $this->templateAuNiveau($key, $departement);
        if ($template === null) {
            $template = new EmailTemplate($key, $subject, $bodyHtml);
            $template->setDepartement($departement);
            $this->em->persist($template);
        } else {
            $template->setSubject($subject);
            $template->setBodyHtml($bodyHtml);
        }
        $this->em->flush();

        return $this->json([
            'id' => $template->getId(),
            'emailKey' => $template->getEmailKey(),
            'departement' => $departement?->getId(),
            'subject' => $template->getSubject(),
            'bodyHtml' => $template->getBodyHtml(),
        ]);
    }

    /**
     * Aperçu d'un modèle avec des valeurs d'exemple (« [nom_variable] ») : { subject, html }.
     *
     * POST /api/email/apercu   { emailKey, subject, bodyHtml }
     */
    #[Route('/apercu', name: 'apercu', methods: ['POST'])]
    public function apercu(Request $request): JsonResponse
    {
        [$definition, $subject, $bodyHtml] = $this->saisie($request);
        try {
            [$objet, $html] = $this->emails->rendrePersonnalisation($subject, $bodyHtml, $this->contexteExemple($definition), $definition->getCategorie());
        } catch (\Throwable $e) {
            return $this->json(['error' => $this->messageErreur($e)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['subject' => $objet, 'html' => $html]);
    }

    /**
     * Envoie l'aperçu du modèle à l'administrateur connecté.
     *
     * POST /api/email/test   { emailKey, subject, bodyHtml }
     */
    #[Route('/test', name: 'test', methods: ['POST'])]
    public function test(Request $request): JsonResponse
    {
        [$definition, $subject, $bodyHtml] = $this->saisie($request);
        $user = $this->getUser();
        $to = $user instanceof Personnel || $user instanceof Etudiant ? ($user->getMailUniv() ?: $user->getMailPerso()) : null;
        if (!$to) {
            return $this->json(['error' => 'Votre compte n\'a pas d\'adresse e-mail.'], Response::HTTP_CONFLICT);
        }
        try {
            [$objet, $html] = $this->emails->rendrePersonnalisation($subject, $bodyHtml, $this->contexteExemple($definition), $definition->getCategorie());
            $this->emails->sendRendered($to, '[Test] '.$objet, $html);
        } catch (\Twig\Error\Error $e) {
            return $this->json(['error' => $this->messageErreur($e)], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return $this->json(['error' => 'L\'e-mail de test n\'a pas pu être envoyé.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['envoyeA' => $to]);
    }

    /**
     * Supprime une personnalisation : le niveau supérieur (établissement, modèle parent, gabarit du package) reprend la main.
     *
     * DELETE /api/email/templates/{id}
     */
    #[Route('/templates/{id}', name: 'template_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteTemplate(int $id): JsonResponse
    {
        $template = $this->templateRepository->find($id) ?? throw $this->createNotFoundException('Modèle introuvable.');
        $this->em->remove($template);
        $this->em->flush();

        return $this->json(['deleted' => true]);
    }

    /**
     * Départements (sélecteur de l'éditeur ; sans département = niveau établissement).
     *
     * GET /api/email/departements
     */
    #[Route('/departements', name: 'departements', methods: ['GET'])]
    public function departements(): JsonResponse
    {
        return $this->json(array_map(static fn (StructureDepartement $d) => [
            'id' => $d->getId(),
            'libelle' => $d->getLibelle(),
        ], $this->departementRepository->findBy(['actif' => true], ['libelle' => 'ASC'])));
    }

    // ---------- Habillage de l'établissement (en-tête, signature, mentions imposées) ----------

    /**
     * GET /api/email/habillage
     */
    #[Route('/habillage', name: 'habillage', methods: ['GET'])]
    public function habillage(): JsonResponse
    {
        $etab = $this->etablissements->findPrincipal() ?? throw $this->createNotFoundException('Aucun établissement configuré.');

        return $this->json([
            'reglages' => $etab->getHabillageEmails(),
            'etablissement' => $etab->getLibelle(),
            'universite' => $etab->getUniversite()->getLibelle(),
            'logo' => $etab->getLogoName() !== null,
        ]);
    }

    /**
     * PUT /api/email/habillage   { couleur, expediteur_nom, reply_to, signature, mentions_legales, contact_rgpd }
     */
    #[Route('/habillage', name: 'habillage_save', methods: ['PUT'])]
    public function enregistrerHabillage(Request $request): JsonResponse
    {
        $etab = $this->etablissements->findPrincipal() ?? throw $this->createNotFoundException('Aucun établissement configuré.');
        [$reglages, $erreurs] = $this->reglagesHabillage(json_decode($request->getContent(), true) ?? []);
        if ($erreurs !== []) {
            return $this->json(['error' => implode("\n", $erreurs), 'erreurs' => $erreurs], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $etab->setHabillageEmails($reglages);
        $this->em->flush();

        return $this->json(['reglages' => $etab->getHabillageEmails()]);
    }

    /**
     * Aperçu d'une notification type avec des réglages non enregistrés : { html }.
     *
     * POST /api/email/habillage/apercu   { reglages }
     */
    #[Route('/habillage/apercu', name: 'habillage_apercu', methods: ['POST'])]
    public function apercuHabillage(Request $request): JsonResponse
    {
        [$reglages, $erreurs] = $this->reglagesHabillage((json_decode($request->getContent(), true) ?? [])['reglages'] ?? []);
        if ($erreurs !== []) {
            return $this->json(['error' => implode("\n", $erreurs)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $exemple = $this->registry->get('notification.immediate') ?? throw $this->createNotFoundException();
        $contexte = $this->emails->habiller($this->contexteExemple($exemple), AbstractEmailDefinition::CATEGORIE_NOTIFICATION, $reglages);
        [, $html] = $this->emails->rendrePersonnalisation('Exemple', (string) $this->emails->corpsParDefaut($exemple), $contexte);

        return $this->json(['html' => $html]);
    }

    /**
     * Réglages d'habillage nettoyés et vérifiés.
     *
     * @param array<string, mixed> $donnees
     *
     * @return array{array<string, string|null>, list<string>}
     */
    private function reglagesHabillage(array $donnees): array
    {
        $texte = static fn (string $cle, int $max) => mb_substr(trim((string) ($donnees[$cle] ?? '')), 0, $max);
        $reglages = [
            'couleur' => $texte('couleur', 7) ?: '#1a3a5c',
            'expediteur_nom' => $texte('expediteur_nom', 100) ?: null,
            'reply_to' => $texte('reply_to', 180) ?: null,
            'signature' => $texte('signature', 500),
            'mentions_legales' => $texte('mentions_legales', 2000),
            'contact_rgpd' => $texte('contact_rgpd', 255) ?: null,
        ];
        $erreurs = [];
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $reglages['couleur'])) {
            $erreurs[] = 'La couleur doit être au format #RRGGBB.';
        }
        if ($reglages['reply_to'] !== null && !filter_var($reglages['reply_to'], \FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L\'adresse de réponse n\'est pas une adresse e-mail valide.';
        }

        return [$reglages, $erreurs];
    }

    // ---------- Outils ----------

    /**
     * Le destinataire peut-il couper cet e-mail ? Notification : selon les modes autorisés de son type (« Jamais ») ;
     * modèle générique : oui ; transactionnel : non, il part toujours.
     */
    private function desactivable(AbstractEmailDefinition $definition): bool
    {
        if ($definition->getCategorie() !== AbstractEmailDefinition::CATEGORIE_NOTIFICATION) {
            return false;
        }
        $type = $this->notifications->get($definition->getKey());

        return $type === null || $type->autorise(ModeNotificationEnum::AUCUN);
    }

    private function departement(int $id): ?StructureDepartement
    {
        return $id > 0 ? $this->departementRepository->find($id) : null;
    }

    private function templateAuNiveau(string $key, ?StructureDepartement $departement): ?EmailTemplate
    {
        return $departement !== null
            ? $this->templateRepository->findByKeyAndDepartement($key, $departement)
            : $this->templateRepository->findGlobal($key);
    }

    /** @return list<string> clés personnalisées au niveau établissement (null) ou d'un département */
    private function clesPersonnalisees(?StructureDepartement $departement): array
    {
        $qb = $this->templateRepository->createQueryBuilder('et')->select('et.emailKey');
        $departement === null
            ? $qb->andWhere('et.departement IS NULL')
            : $qb->andWhere('et.departement = :d')->setParameter('d', $departement);

        return array_column($qb->getQuery()->getScalarResult(), 'emailKey');
    }

    /**
     * Ce qui s'applique au niveau consulté : « departement », « etablissement », « parent » (modèle générique
     * personnalisé) ou « defaut » (gabarit du package).
     *
     * @param list<string> $globales
     * @param list<string> $locales
     */
    private function niveau(AbstractEmailDefinition $def, ?StructureDepartement $departement, array $globales, array $locales): string
    {
        $parent = $def->getParentKey();

        return match (true) {
            $departement !== null && \in_array($def->getKey(), $locales, true) => 'departement',
            \in_array($def->getKey(), $globales, true) => 'etablissement',
            $parent !== null && (\in_array($parent, $locales, true) || \in_array($parent, $globales, true)) => 'parent',
            default => 'defaut',
        };
    }

    /** @return array{AbstractEmailDefinition, string, string} */
    private function saisie(Request $request): array
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $definition = $this->registry->get((string) ($data['emailKey'] ?? '')) ?? throw $this->createNotFoundException('Clé d\'email inconnue.');

        return [$definition, (string) ($data['subject'] ?? ''), (string) ($data['bodyHtml'] ?? '')];
    }

    /** Message d'erreur si le modèle ne se rend pas avec les variables déclarées, null s'il est valide. */
    private function verifier(AbstractEmailDefinition $definition, string $subject, string $bodyHtml): ?string
    {
        try {
            $this->emails->rendrePersonnalisation($subject, $bodyHtml, $this->contexteExemple($definition), $definition->getCategorie());

            return null;
        } catch (\Throwable $e) {
            return $this->messageErreur($e);
        }
    }

    private function messageErreur(\Throwable $e): string
    {
        $message = $e instanceof \Twig\Error\Error ? $e->getRawMessage() : $e->getMessage();
        if ($e instanceof \Twig\Sandbox\SecurityError) {
            $message = 'Construction non autorisée dans un modèle personnalisé : '.$message;
        }
        $ligne = $e instanceof \Twig\Error\Error && $e->getTemplateLine() > 0 ? sprintf(' (ligne %d)', $e->getTemplateLine()) : '';

        return 'Modèle invalide'.$ligne.' : '.$message;
    }

    /**
     * Une valeur d'exemple par variable déclarée : un lien pour les URL, une date pour les dates (filtre « date »),
     * « [nom] » sinon, pour repérer la variable dans l'aperçu.
     *
     * @return array<string, string>
     */
    private function contexteExemple(AbstractEmailDefinition $definition): array
    {
        $contexte = [];
        foreach (array_keys($definition->getAvailableVariables()) as $nom) {
            $contexte[$nom] = match (true) {
                (bool) preg_match('/url/i', $nom) => '/app',
                (bool) preg_match('/(date|At$|_le$|limite|expire)/', $nom) => date('Y-m-d H:i'),
                default => '['.$nom.']',
            };
        }

        return $contexte;
    }
}
