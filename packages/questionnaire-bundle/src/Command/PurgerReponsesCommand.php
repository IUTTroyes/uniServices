<?php

namespace QuestionnaireBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireAnswer;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Services\Analytics\QuestionnaireAnalyticsService;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Durée de conservation (RGPD) : pour chaque questionnaire clôturé depuis plus de N années (réglage du département,
 * 5 ans par défaut), archive les statistiques agrégées puis supprime les réponses et anonymise les invitations
 * (adresse, lien, historique des relances). Les résultats restent consultables et comparables d'une année sur l'autre.
 */
#[AsCommand(
    name: 'questionnaire:purger',
    description: 'Purge les réponses des questionnaires clôturés au-delà de la durée de conservation (statistiques agrégées conservées)',
)]
final class PurgerReponsesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireAnalyticsService $analytics,
        private readonly QuestionnaireReglages $reglages,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Lister sans rien supprimer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $simulation = (bool) $input->getOption('dry-run');
        $maintenant = new \DateTimeImmutable();
        $purges = 0;

        $candidats = $this->em->getRepository(Questionnaire::class)->createQueryBuilder('q')
            ->where('q.status = :clos')->setParameter('clos', QuestStatutEnum::CLOSED)
            ->andWhere('q.purgeLe IS NULL')
            ->getQuery()->getResult();

        foreach ($candidats as $q) {
            $cloture = $q->getClotureLe() ?? ($q->getClosingDate() ? \DateTimeImmutable::createFromInterface($q->getClosingDate()) : null);
            $annees = $this->reglages->pour($q->getDepartement())['conservation_annees'];
            if ($cloture === null || $cloture->modify(sprintf('+%d years', $annees)) > $maintenant) {
                continue;
            }
            $io->writeln(sprintf(' • %s (clôturé le %s, conservation %d an(s))', $q->getTitle(), $cloture->format('d/m/Y'), $annees));
            ++$purges;
            if ($simulation) {
                continue;
            }

            // 1. Statistiques agrégées figées (aucune réponse individuelle, seuils d'anonymat déjà appliqués).
            $q->archiverResultats(json_decode((string) json_encode($this->analytics->getAnalytics($q), \JSON_PRESERVE_ZERO_FRACTION), true));
            // 2. Suppression des réponses, anonymisation des invitations.
            $this->em->createQueryBuilder()->delete(QuestionnaireAnswer::class, 'a')
                ->where('a.invitation IN (:invitations)')->setParameter('invitations', $q->getInvitations())
                ->getQuery()->execute();
            foreach ($q->getInvitations() as $invitation) {
                $invitation->anonymiser();
            }
            $this->em->flush();
        }

        $io->success(sprintf('%d questionnaire(s) %s.', $purges, $simulation ? 'à purger' : 'purgé(s)'));

        return Command::SUCCESS;
    }
}
