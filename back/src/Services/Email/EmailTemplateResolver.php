<?php

namespace App\Services\Email;

use App\Entity\Email\EmailTemplate;
use App\Entity\Structure\StructureDepartement;
use App\Repository\Email\EmailTemplateRepository;

/**
 * Résout le template à utiliser pour un email donné selon la hiérarchie :
 *
 *   1. Personnalisation BDD spécifique au département
 *   2. Personnalisation BDD globale (departement = null)
 *   3. Template Twig fichier du package (valeur par défaut)
 */
final class EmailTemplateResolver
{
    public function __construct(
        private readonly EmailTemplateRepository $repository,
        private readonly EmailRegistry $registry,
    ) {
    }

    /**
     * Résout le template à utiliser.
     *
     * @return ResolvedEmailTemplate Contient la source (BDD ou fichier Twig)
     */
    public function resolve(
        string $emailKey,
        ?StructureDepartement $departement = null,
        string $locale = 'fr',
    ): ResolvedEmailTemplate {
        // 1. Recherche personnalisation département
        if ($departement !== null) {
            $template = $this->repository->findByKeyAndDepartement($emailKey, $departement, $locale);
            if ($template !== null) {
                return ResolvedEmailTemplate::fromDatabase($template);
            }
        }

        // 2. Recherche personnalisation globale (sans département)
        $global = $this->repository->findGlobal($emailKey, $locale);
        if ($global !== null) {
            return ResolvedEmailTemplate::fromDatabase($global);
        }

        // 3. Fallback sur le template Twig du package
        $definition = $this->registry->get($emailKey);
        if ($definition === null) {
            throw new \InvalidArgumentException(sprintf(
                'Aucune définition d\'email trouvée pour la clé "%s". Assurez-vous que la classe est taggée "app.email_definition".',
                $emailKey
            ));
        }

        return ResolvedEmailTemplate::fromDefinition($definition);
    }
}
