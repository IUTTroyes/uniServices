<?php

namespace QuestionnaireBundle\Domain\Questionnaire\Publish;

use QuestionnaireBundle\Domain\Questionnaire\Structure\QuestionnaireStructureService;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotifier;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class PublishQuestionnaireService
{
    public function __construct(
        private readonly EntityManagerInterface        $em,
        private readonly QuestionnaireStructureService $structureService,
        private readonly QuestionnaireNotifier $notifier,
        private readonly QuestionnaireReglages $reglages,
    ) {}

    /**
     * @param list<string> $recipientEmails
     * @return array{sectionsCount:int,invitationsCount:int,emailsCount:int,publishedAt:\DateTimeImmutable}
     */
    public function publish(Questionnaire $q, array $recipientEmails): array
    {
        if ($q->isEstModele()) {
            throw new ConflictHttpException('Un modèle ne se publie pas : créez un questionnaire à partir de ce modèle.');
        }
        if (!$q->estBrouillon()) {
            throw new ConflictHttpException('Ce questionnaire est déjà publié.');
        }

        // Générer QuestionnaireSectionInstance (structure figée)
        $plan = $this->structureService->buildPlan($q);
        if ($plan === []) {
            throw new UnprocessableEntityHttpException('Le questionnaire ne contient aucune section à publier.');
        }

        $emails = self::adresses($recipientEmails);

        // Geler questionnaire ; sans date de clôture, celle du département (durée par défaut).
        $this->reglages->appliquerALaPublication($q);
        $q->publishNow();
        foreach ($plan as $row) {
            $psi = new QuestionnaireSectionInstance(
                q: $q,
                st: $row['sectionTemplate'],
                order: $row['sortOrder'],
                titleSnapshot: $row['title'],
                repeatItemType: $row['repeatItemType'],
                repeatItemId: $row['repeatItemId']
            );
            $psi->setEnseignement($row['enseignementId'] !== null ? $this->em->find(ScolEnseignement::class, $row['enseignementId']) : null);
            $psi->setPersonnelCible($row['personnelId'] !== null ? $this->em->find(Personnel::class, $row['personnelId']) : null);
            $this->em->persist($psi);
        }

        $invitations = $this->creerInvitations($q, $emails);
        $this->em->flush();
        $envoyes = $this->envoyer($invitations);

        return [
            'sectionsCount' => count($plan),
            'invitationsCount' => count($invitations),
            'emailsCount' => $envoyes,
            'publishedAt' => $q->getPublishedAt() ?? new \DateTimeImmutable(),
        ];
    }

    /**
     * Invite de nouveaux participants à un questionnaire déjà publié (les adresses déjà invitées sont ignorées).
     *
     * @param list<string> $recipientEmails
     * @return array{invitationsCount:int,emailsCount:int,ignores:int}
     */
    public function inviter(Questionnaire $q, array $recipientEmails): array
    {
        if ($q->estBrouillon()) {
            throw new ConflictHttpException('Publiez le questionnaire avant d\'inviter des participants.');
        }
        // On peut inviter avant l'ouverture, pas après la clôture.
        if (!$q->isPublished() || ($q->getClosingDate() !== null && $q->getClosingDate() < new \DateTime())) {
            throw new ConflictHttpException('Ce questionnaire est clôturé : impossible d\'inviter de nouveaux participants.');
        }
        $emails = self::adresses($recipientEmails);
        $dejaInvites = [];
        foreach ($q->getInvitations() as $invitation) {
            $dejaInvites[mb_strtolower((string) $invitation->getEmail())] = true;
        }
        $nouvelles = array_values(array_diff($emails, array_keys($dejaInvites)));

        $invitations = $this->creerInvitations($q, $nouvelles);
        $this->em->flush();

        return [
            'invitationsCount' => count($invitations),
            'emailsCount' => $this->envoyer($invitations),
            'ignores' => count($emails) - count($nouvelles),
        ];
    }

    /**
     * Adresses valides, en minuscules, sans doublon (une invitation par adresse) ; 422 sur une adresse invalide.
     *
     * @param array<mixed> $adresses
     * @return list<string>
     */
    public static function adresses(array $adresses): array
    {
        $emails = [];
        foreach ($adresses as $email) {
            $email = mb_strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }
            if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                throw new UnprocessableEntityHttpException(sprintf('Adresse e-mail invalide : %s', $email));
            }
            $emails[$email] = true;
        }

        return array_keys($emails);
    }

    /**
     * Une invitation par adresse ; le jeton (256 bits aléatoires) est la clé d'accès du répondant.
     *
     * @param list<string> $emails
     * @return list<QuestionnaireInvitation>
     */
    private function creerInvitations(Questionnaire $q, array $emails): array
    {
        $invitations = [];
        foreach ($emails as $email) {
            $invitation = new QuestionnaireInvitation($q, bin2hex(random_bytes(32)), $email);
            $q->addQuestionnaireInvitation($invitation);
            $this->em->persist($invitation);
            $invitations[] = $invitation;
        }

        return $invitations;
    }

    /**
     * Invitations transmises après l'enregistrement : notification (compte de l'application) ou e-mail direct (adresse
     * extérieure). Un échec d'envoi n'annule pas l'invitation : une relance reste possible.
     *
     * @param list<QuestionnaireInvitation> $invitations
     */
    private function envoyer(array $invitations): int
    {
        $envoyes = 0;
        foreach ($invitations as $invitation) {
            if ($this->notifier->invitation($invitation)) {
                ++$envoyes;
            }
        }

        return $envoyes;
    }
}
