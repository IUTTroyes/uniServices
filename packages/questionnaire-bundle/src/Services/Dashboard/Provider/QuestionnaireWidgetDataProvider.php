<?php

namespace QuestionnaireBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\DashboardContext;
use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\QueryBuilder;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Enum\QuestInvitationStatusEnum;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireInvitationRepository;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\State\Provider\Questionnaire\Runtime\MesInvitationsProvider;

/**
 * Données des widgets du portail, limitées à ce que la personne peut voir :
 *  - questionnaire.pending : SES questionnaires à remplir (invitations ouvertes et non envoyées) ;
 *  - questionnaire.stats : questionnaires qu'elle gère (départements gérés et questionnaires dont elle est l'auteur) ;
 *  - questionnaire.last_answers : dernières réponses envoyées à ces questionnaires (date et titre, jamais l'identité).
 */
class QuestionnaireWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly QuestionnaireRepository $questionnaires,
        private readonly QuestionnaireInvitationRepository $invitations,
        private readonly QuestionnaireAccess $access,
    ) {
    }

    public function supports(string $code): bool
    {
        return str_starts_with($code, 'questionnaire.');
    }

    public function getData(string $code, Personnel|Etudiant $user, ?DashboardContext $context = null): array
    {
        return match ($code) {
            'questionnaire.pending' => ['items' => $this->aRemplir($user)],
            'questionnaire.stats' => $this->statistiques($user),
            'questionnaire.last_answers' => ['items' => $this->dernieresReponses($user)],
            default => [],
        };
    }

    /** @return list<array{titre: string, echeance: ?string, lien: string}> */
    private function aRemplir(Personnel|Etudiant $user): array
    {
        $items = [];
        foreach ($this->invitations->findPourAdresses(MesInvitationsProvider::adresses($user)) as $invitation) {
            $q = $invitation->getQuestionnaire();
            if ($invitation->isSubmitted() || !$q->estOuvert()) {
                continue;
            }
            $items[] = [
                'titre' => (string) $q->getTitle(),
                'echeance' => $q->getClosingDate()?->format('d/m/Y'),
                'lien' => '/questionnaire/qualite/enquetes/take/'.$invitation->getToken(),
            ];
            if (\count($items) >= 5) {
                break;
            }
        }

        return $items;
    }

    /** @return array{total: int, published: int, draft: int, closed: int} */
    private function statistiques(Personnel|Etudiant $user): array
    {
        $stats = ['total' => 0, 'published' => 0, 'draft' => 0, 'closed' => 0];
        $qb = $this->questionnaires->createQueryBuilder('q')->select('q.status AS statut, COUNT(q.id) AS nombre')->groupBy('q.status');
        if (!$this->restreindre($qb, 'q', $user)) {
            return $stats;
        }
        foreach ($qb->getQuery()->getArrayResult() as $ligne) {
            $statut = $ligne['statut'] instanceof QuestStatutEnum ? $ligne['statut']->value : (string) $ligne['statut'];
            $stats[$statut] = (int) $ligne['nombre'];
            $stats['total'] += (int) $ligne['nombre'];
        }

        return $stats;
    }

    /** @return list<string> */
    private function dernieresReponses(Personnel|Etudiant $user): array
    {
        $qb = $this->invitations->createQueryBuilder('i')
            ->addSelect('q')
            ->join('i.questionnaire', 'q')
            ->where('i.status = :soumise')
            ->setParameter('soumise', QuestInvitationStatusEnum::SUBMITTED)
            ->orderBy('i.submittedAt', 'DESC')
            ->setMaxResults(5);
        if (!$this->restreindre($qb, 'q', $user)) {
            return [];
        }

        return array_map(
            static fn (QuestionnaireInvitation $i) => sprintf('%s — %s', $i->getQuestionnaire()?->getTitle(), $i->getSubmittedAt()?->format('d/m H:i') ?? ''),
            $qb->getQuery()->getResult(),
        );
    }

    /** Même périmètre que QuestionnaireVisibiliteExtension ; false si la personne ne voit aucun questionnaire. */
    private function restreindre(QueryBuilder $qb, string $alias, Personnel|Etudiant $user): bool
    {
        if ($this->access->estSuperAdmin($user)) {
            return true;
        }
        if (!$user instanceof Personnel) {
            return false;
        }
        $conditions = [sprintf('%s.author = :widgetAuteur', $alias)];
        $qb->setParameter('widgetAuteur', $user);
        $departements = $this->access->departementsVisibles($user);
        if ($departements !== []) {
            $conditions[] = sprintf('IDENTITY(%s.departement) IN (:widgetDepartements)', $alias);
            $qb->setParameter('widgetDepartements', $departements);
        }
        $qb->andWhere('('.implode(' OR ', $conditions).')');

        return true;
    }
}
