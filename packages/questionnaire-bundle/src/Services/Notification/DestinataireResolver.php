<?php

namespace QuestionnaireBundle\Services\Notification;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Compte de l'application correspondant à une adresse d'invitation (adresse universitaire ou personnelle).
 * Sans compte (adresse extérieure), l'invitation passe par un e-mail direct.
 */
class DestinataireResolver
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function pourAdresse(?string $email): Etudiant|Personnel|null
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email === '') {
            return null;
        }
        foreach ([Etudiant::class, Personnel::class] as $classe) {
            $compte = $this->em->getRepository($classe)->createQueryBuilder('u')
                ->where('LOWER(u.mailUniv) = :email OR LOWER(u.mailPerso) = :email')
                ->setParameter('email', $email)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            if ($compte !== null) {
                return $compte;
            }
        }

        // Pas de cache : le service est partagé et des entités gardées d'une requête à l'autre seraient détachées.
        return null;
    }
}
