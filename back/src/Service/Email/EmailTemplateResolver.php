<?php

namespace App\Service\Email;

use App\Entity\Email\EmailTemplate;
use App\Entity\Structure\StructureDepartement;
use App\Repository\Email\EmailTemplateRepository;

/**
 * Résout le template à utiliser pour un email donné selon la hiérarchie :
 *
 *   1. Personnalisation BDD spécifique au département
 *   2. Personnalisation BDD de l'établissement (departement = null)
 *   3. Personnalisation (département, puis établissement) de l'e-mail parent, s'il en a un
 *      (ex. : une notification hérite du modèle générique « notification.immediate »)
 *   4. Template Twig fichier du package (valeur par défaut)
 */
final class EmailTemplateResolver
{
    public function __construct(
        private readonly EmailTemplateRepository $repository,
        private readonly EmailRegistry $registry,
    ) {
    }

    public function resolve(
        string $emailKey,
        ?StructureDepartement $departement = null,
        string $locale = 'fr',
    ): ResolvedEmailTemplate {
        $definition = $this->registry->get($emailKey);
        if ($definition === null) {
            throw new \InvalidArgumentException(sprintf(
                'Aucune définition d\'email trouvée pour la clé "%s". Assurez-vous que la classe est taggée "app.email_definition".',
                $emailKey
            ));
        }

        foreach (array_filter([$emailKey, $definition->getParentKey()]) as $cle) {
            $template = $this->personnalisation($cle, $departement, $locale);
            if ($template !== null) {
                return ResolvedEmailTemplate::fromDatabase($template, $definition);
            }
        }

        return ResolvedEmailTemplate::fromDefinition($definition);
    }

    /** Personnalisation du département, à défaut de l'établissement. */
    private function personnalisation(string $cle, ?StructureDepartement $departement, string $locale): ?EmailTemplate
    {
        if ($departement !== null) {
            $template = $this->repository->findByKeyAndDepartement($cle, $departement, $locale);
            if ($template !== null) {
                return $template;
            }
        }

        return $this->repository->findGlobal($cle, $locale);
    }
}
