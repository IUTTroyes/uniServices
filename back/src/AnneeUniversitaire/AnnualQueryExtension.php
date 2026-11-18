<?php

declare(strict_types=1);

namespace App\AnneeUniversitaire;

use ApiPlatform\Doctrine\Orm\Extension\{QueryCollectionExtensionInterface, QueryItemExtensionInterface};
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class AnnualQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private PerimetreAnnuelRegistry $registry, private AnneeUniversitaireContext $context) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass === \App\Entity\Users\Etudiant::class && $this->context->request()?->query->getBoolean('sansScolarite')) {
            $alias = $queryNameGenerator->generateJoinAlias('sans_scolarite');
            $queryBuilder->leftJoin($queryBuilder->getRootAliases()[0].'.scolarites', $alias)->andWhere($alias.'.id IS NULL');
            return;
        }
        if ($resourceClass === \QuestionnaireBundle\Entity\Questionnaires\Questionnaire::class && $this->context->request()?->query->getBoolean('estModele')) {
            $queryBuilder->andWhere($queryBuilder->getRootAliases()[0].'.estModele = true');
            return;
        }
        if ($this->registry->path($resourceClass) === null || $this->context->toutesAutorisees($resourceClass)) return;
        $this->context->apply($queryBuilder, $resourceClass, $queryBuilder->getRootAliases()[0]);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass === \App\Entity\Users\Etudiant::class || $this->registry->path($resourceClass) === null) return;
        $request = $this->context->request();
        if ($request === null) return;
        $object = $queryBuilder->getEntityManager()->getRepository($resourceClass)->findOneBy($identifiers);
        if ($object === null) return;
        $years = $this->context->years($object);
        $explicit = $this->context->explicit($request);
        if ($explicit === null && $request->headers->get(AnneeUniversitaireContext::MODE_HEADER) === 'explicite') {
            $explicit = $this->context->id($request->headers->get(AnneeUniversitaireContext::HEADER));
        }
        if ($explicit !== null && !in_array($explicit, $years, true)) throw new ConflictHttpException('L’année explicite ne correspond pas à la ressource demandée.');
        if (count($years) === 1) $request->attributes->set('_annee_universitaire_id', $years[0]);
    }
}
