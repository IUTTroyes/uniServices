<?php

namespace QuestionnaireBundle\EventListener;

use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
final class QuestionnaireAuthorListener
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Questionnaire) {
            return;
        }

        if (null === $entity->getAuthor()) {
            $user = $this->security->getUser();
            if ($user instanceof Personnel) {
                $entity->setAuthor($user);
            }
        }
    }
}
