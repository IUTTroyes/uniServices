<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Commentaire;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Enum\TypeNotificationEnum;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Repository\CommentaireRepository;
use UnifolioBundle\Service\Notification\NotificationManager;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Fil de discussion d'une trace entre l'étudiant et ses évaluateurs. */
class CommentaireService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommentaireRepository $commentaires,
        private readonly NotificationManager $notifications,
        private readonly CampagneAffectationRepository $affectations,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function lister(Trace $trace, Etudiant|Personnel $moi): array
    {
        return array_map(fn (Commentaire $c) => $this->normaliser($c, $moi), $this->commentaires->findParTrace($trace));
    }

    /** @return array<string, mixed> */
    public function ajouter(Trace $trace, Etudiant|Personnel $auteur, string $contenu, ?int $rattachementId = null, ?string $parentUuid = null): array
    {
        $rattachement = null;
        if ($rattachementId !== null) {
            foreach ($trace->getRattachements() as $r) {
                if ($r->getId() === $rattachementId) {
                    $rattachement = $r;
                }
            }
            if ($rattachement === null) {
                throw UnifolioValidationException::un('Ce rattachement n\'appartient pas à la trace.');
            }
        }
        $parent = null;
        if ($parentUuid !== null && $parentUuid !== '') {
            $parent = $this->commentaires->findOneByUuid($parentUuid);
            if ($parent === null || $parent->getTrace() !== $trace) {
                throw UnifolioValidationException::un('Commentaire parent introuvable.');
            }
        }

        try {
            $commentaire = $auteur instanceof Etudiant
                ? Commentaire::parEtudiant($trace, $auteur, $contenu, $rattachement, $parent)
                : Commentaire::parPersonnel($trace, $auteur, $contenu, $rattachement, $parent);
        } catch (\InvalidArgumentException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
        $this->em->persist($commentaire);
        $this->em->flush();
        $this->notifier($trace, $auteur);

        return $this->normaliser($commentaire, $auteur);
    }

    /** @return array<string, mixed> */
    /** Un enseignant commente : l'étudiant est prévenu ; un étudiant commente : ses évaluateurs le sont. */
    private function notifier(Trace $trace, Etudiant|Personnel $auteur): void
    {
        if ($auteur instanceof Personnel) {
            $this->notifications->notifier(
                $trace->getEtudiant(),
                TypeNotificationEnum::COMMENTAIRE_ENSEIGNANT,
                sprintf('%s a commenté votre trace « %s ».', $auteur->getDisplay(), $trace->getTitre()),
                '/app/unifolio/traces/'.$trace->getUuidString(),
            );

            return;
        }
        foreach ($this->affectations->evaluateursDe($auteur) as $evaluateur) {
            $this->notifications->notifier(
                $evaluateur,
                TypeNotificationEnum::COMMENTAIRE_ETUDIANT,
                sprintf('%s a commenté sa trace « %s ».', $auteur->getDisplay(), $trace->getTitre()),
                '/app/unifolio/evaluations/traces/'.$trace->getUuidString(),
            );
        }
    }

    private function normaliser(Commentaire $c, Etudiant|Personnel $moi): array
    {
        $auteur = $c->getAuteurEtudiant() ?? $c->getAuteurPersonnel();
        $estMoi = $auteur !== null && $auteur::class === $moi::class && $auteur->getId() === $moi->getId();

        return [
            'uuid' => $c->getUuidString(),
            'parent' => $c->getParent()?->getUuidString(),
            'rattachementId' => $c->getRattachement()?->getId(),
            'auteur' => [
                'type' => $c->isDeEtudiant() ? 'etudiant' : 'personnel',
                'nom' => $auteur?->getDisplay() ?? 'Utilisateur supprimé',
                'moi' => $estMoi,
            ],
            'contenu' => $c->getContenu(),
            'creeLe' => $c->getCreatedAt()?->format(\DATE_ATOM),
        ];
    }
}
