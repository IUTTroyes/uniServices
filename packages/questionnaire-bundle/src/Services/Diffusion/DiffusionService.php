<?php

namespace QuestionnaireBundle\Services\Diffusion;

use App\Entity\Edt\EdtEvent;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireDiffusion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotifier;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Diffusion ciblée des résultats : qui reçoit les résultats de quelle section.
 *
 *  1. pré-remplissage : l'intervenant évalué (section répétée sur le prévisionnel), sinon les intervenants de
 *     l'enseignement d'après le prévisionnel de l'année (package intranet, s'il est installé), sinon d'après l'emploi
 *     du temps ; les sections générales restent à compléter à la main ;
 *  2. validation par l'équipe (liste modifiable) ;
 *  3. publication (manuelle, ou automatique à la fin de la relecture) : chaque destinataire est notifié une fois et
 *     ne voit que les sections qui le concernent.
 */
class DiffusionService
{
    /** Le prévisionnel appartient au package intranet : lu seulement s'il est installé (pas de dépendance dure). */
    private const PREVISIONNEL = 'IntranetBundle\Entity\Previsionnel\Previsionnel';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireReglages $reglages,
        private readonly QuestionnaireNotifier $notifier,
    ) {
    }

    /** @return list<QuestionnaireSectionInstance> */
    public function sections(Questionnaire $q): array
    {
        return $this->em->getRepository(QuestionnaireSectionInstance::class)->findBy(['questionnaire' => $q], ['sortOrder' => 'ASC']);
    }

    /**
     * Destinataires suggérés pour une section.
     *
     * @return list<array{0: Personnel, 1: string}> [personnel, origine]
     */
    public function suggestions(QuestionnaireSectionInstance $section): array
    {
        if ($section->getPersonnelCible() !== null) {
            return [[$section->getPersonnelCible(), QuestionnaireDiffusion::ORIGINE_INTERVENANT]];
        }
        $enseignement = $section->getEnseignement();
        if ($enseignement === null) {
            return [];
        }
        $annee = $section->getQuestionnaire()?->getAnneeUniversitaire();

        if (class_exists(self::PREVISIONNEL)) {
            $qb = $this->em->getRepository(self::PREVISIONNEL)->createQueryBuilder('p')
                ->where('p.enseignement = :enseignement')->setParameter('enseignement', $enseignement)
                ->andWhere('p.personnel IS NOT NULL')
                ->orderBy('p.referent', 'DESC');
            if ($annee !== null) {
                $qb->andWhere('p.anneeUniversitaire = :annee')->setParameter('annee', $annee);
            }
            $personnels = [];
            foreach ($qb->getQuery()->getResult() as $previsionnel) {
                $personnel = $previsionnel->getPersonnel();
                $personnels[$personnel->getId()] ??= [$personnel, QuestionnaireDiffusion::ORIGINE_PREVISIONNEL];
            }
            if ($personnels !== []) {
                return array_values($personnels);
            }
        }

        $qb = $this->em->getRepository(EdtEvent::class)->createQueryBuilder('e')
            ->select('IDENTITY(e.personnel) AS id')->distinct()
            ->where('e.enseignement = :enseignement')->setParameter('enseignement', $enseignement)
            ->andWhere('e.personnel IS NOT NULL');
        if ($annee !== null) {
            $qb->andWhere('e.anneeUniversitaire = :annee')->setParameter('annee', $annee);
        }
        $ids = array_column($qb->getQuery()->getScalarResult(), 'id');

        return array_map(
            static fn (Personnel $p) => [$p, QuestionnaireDiffusion::ORIGINE_EDT],
            $ids === [] ? [] : $this->em->getRepository(Personnel::class)->findBy(['id' => $ids]),
        );
    }

    /** Ajoute les destinataires suggérés manquants (sans retirer ceux qui ont été choisis à la main). */
    public function preremplir(Questionnaire $q): int
    {
        $ajouts = 0;
        foreach ($this->sections($q) as $section) {
            foreach ($this->suggestions($section) as [$personnel, $origine]) {
                if ($section->ajouterDestinataire($personnel, $origine) !== null) {
                    ++$ajouts;
                }
            }
        }
        $q->setDiffusionInitialisee(true);
        $this->em->flush();

        return $ajouts;
    }

    /**
     * Remplace les destinataires des sections (identifiant de section → identifiants de personnels) et, sur demande,
     * valide la diffusion. Après publication, les nouveaux destinataires sont notifiés aussitôt.
     *
     * @param array<int|string, mixed> $destinataires corps JSON : identifiant de section → liste d'identifiants de personnels
     */
    public function enregistrer(Questionnaire $q, array $destinataires, bool $valider, ?Personnel $par): void
    {
        if ($q->getStatus() === QuestStatutEnum::DRAFT) {
            throw new ConflictHttpException('Publiez le questionnaire avant de préparer la diffusion de ses résultats.');
        }
        $sections = [];
        foreach ($this->sections($q) as $section) {
            $sections[$section->getId()] = $section;
        }

        foreach ($destinataires as $sectionId => $ids) {
            $section = $sections[(int) $sectionId] ?? throw new UnprocessableEntityHttpException(sprintf('La section %s n\'appartient pas à ce questionnaire.', $sectionId));
            $voulus = array_values(array_unique(array_map('intval', \is_array($ids) ? $ids : [])));
            $personnels = $voulus === [] ? [] : $this->em->getRepository(Personnel::class)->findBy(['id' => $voulus]);
            if (\count($personnels) !== \count($voulus)) {
                throw new UnprocessableEntityHttpException('Destinataire inconnu.');
            }
            foreach ($section->getDiffusions()->toArray() as $diffusion) {
                if (!\in_array($diffusion->getPersonnel()->getId(), $voulus, true)) {
                    $section->retirerDestinataire($diffusion);
                }
            }
            foreach ($personnels as $personnel) {
                $section->ajouterDestinataire($personnel, QuestionnaireDiffusion::ORIGINE_MANUELLE);
            }
        }

        $q->setDiffusionInitialisee(true);
        if ($valider) {
            $q->validerDiffusion($par);
        }
        $this->em->flush();

        if ($q->resultatsPublies()) {
            $this->notifierNonNotifies($q);
        }
    }

    /**
     * Publie les résultats aux destinataires (questionnaire clôturé, diffusion validée).
     *
     * @return int personnes notifiées
     */
    public function publier(Questionnaire $q): int
    {
        if ($q->getStatus() !== QuestStatutEnum::CLOSED) {
            throw new ConflictHttpException('Clôturez le questionnaire avant de publier ses résultats.');
        }
        if ($q->getDiffusionValideeLe() === null) {
            throw new ConflictHttpException('Validez la liste des destinataires avant de publier les résultats.');
        }
        $q->publierResultats();
        $this->em->flush();

        return $this->notifierNonNotifies($q);
    }

    /** Fin de la relecture : publication automatique si le département l'autorise et que la diffusion est validée. */
    public function publierSiEcheance(Questionnaire $q, \DateTimeImmutable $maintenant): bool
    {
        $reglages = $this->reglages->pour($q->getDepartement());
        if (!$reglages['publication_auto'] || $q->resultatsPublies() || $q->getStatus() !== QuestStatutEnum::CLOSED
            || $q->getDiffusionValideeLe() === null || $q->getClotureLe() === null) {
            return false;
        }
        if ($q->getClotureLe()->modify(sprintf('+%d days', $reglages['delai_relecture_jours'])) > $maintenant) {
            return false;
        }
        $this->publier($q);

        return true;
    }

    /** Fin prévue de la relecture (null si la publication automatique est désactivée ou le questionnaire non clôturé). */
    public function finRelecture(Questionnaire $q): ?\DateTimeImmutable
    {
        $reglages = $this->reglages->pour($q->getDepartement());

        return $reglages['publication_auto'] && $q->getClotureLe() !== null
            ? $q->getClotureLe()->modify(sprintf('+%d days', $reglages['delai_relecture_jours']))
            : null;
    }

    /** Une notification par personne (toutes ses sections), seulement pour les destinataires pas encore prévenus. */
    private function notifierNonNotifies(Questionnaire $q): int
    {
        $parPersonne = [];
        foreach ($this->sections($q) as $section) {
            foreach ($section->getDiffusions() as $diffusion) {
                if ($diffusion->getNotifieLe() === null) {
                    $parPersonne[$diffusion->getPersonnel()->getId()][] = $diffusion;
                }
            }
        }
        foreach ($parPersonne as $diffusions) {
            $titres = array_map(static fn (QuestionnaireDiffusion $d) => (string) $d->getSectionInstance()->getTitleSnapshot(), $diffusions);
            $this->notifier->resultats($q, $diffusions[0]->getPersonnel(), $titres);
            foreach ($diffusions as $diffusion) {
                $diffusion->marquerNotifie();
            }
        }
        $this->em->flush();

        return \count($parPersonne);
    }
}
