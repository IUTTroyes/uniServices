<?php

namespace QuestionnaireBundle\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Users\Personnel;
use Doctrine\ORM\QueryBuilder;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Listes de l'API restreintes aux questionnaires que la personne peut voir (cf. QuestionnaireAccess) :
 *  - super administrateur : tout ;
 *  - personnel : questionnaires des départements dont il gère les questionnaires, et ceux dont il est l'auteur ;
 *  - tout autre profil : rien.
 *
 * S'applique aux questionnaires et à ce qui en dépend (sections, questions, invitations).
 */
final class QuestionnaireVisibiliteExtension implements QueryCollectionExtensionInterface
{
    /** Chemin depuis la ressource jusqu'au questionnaire. */
    private const CHEMINS = [
        Questionnaire::class => [],
        QuestionnaireSection::class => ['questionnaire'],
        QuestionnaireQuestion::class => ['section', 'questionnaire'],
        QuestionnaireInvitation::class => ['questionnaire'],
    ];

    public function __construct(
        private readonly Security $security,
        private readonly QuestionnaireAccess $access,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!\array_key_exists($resourceClass, self::CHEMINS)) {
            return;
        }
        $user = $this->security->getUser();
        if ($this->access->estSuperAdmin($user)) {
            return;
        }
        if (!$user instanceof Personnel) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        foreach (self::CHEMINS[$resourceClass] as $association) {
            $alias = $this->jointure($queryBuilder, $queryNameGenerator, $alias, $association);
        }

        $auteur = $queryNameGenerator->generateParameterName('questAuteur');
        $conditions = [sprintf('%s.author = :%s', $alias, $auteur)];
        $queryBuilder->setParameter($auteur, $user);

        $departements = $this->access->departementsVisibles($user);
        if ($departements !== []) {
            $param = $queryNameGenerator->generateParameterName('questDepartements');
            $conditions[] = sprintf('IDENTITY(%s.departement) IN (:%s)', $alias, $param);
            $queryBuilder->setParameter($param, $departements);
        }

        // Modèles d'établissement : bibliothèque commune à tous les gestionnaires.
        if ($departements !== []) {
            $conditions[] = sprintf('(%s.estModele = true AND %s.departement IS NULL)', $alias, $alias);
        }

        $queryBuilder->andWhere('('.implode(' OR ', $conditions).')');
    }

    /** Alias de la jointure « $depuis.$association », créée (INNER JOIN) si la requête ne l'a pas déjà. */
    private function jointure(QueryBuilder $qb, QueryNameGeneratorInterface $noms, string $depuis, string $association): string
    {
        foreach ($qb->getDQLPart('join') as $jointures) {
            foreach ($jointures as $jointure) {
                if ($jointure->getJoin() === "$depuis.$association") {
                    return (string) $jointure->getAlias();
                }
            }
        }
        $alias = $noms->generateJoinAlias($association);
        $qb->innerJoin("$depuis.$association", $alias);

        return $alias;
    }
}
