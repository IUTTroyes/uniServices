<?php

namespace App\EventListener;

use App\Entity\Users\Etudiant;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();

        if ($user instanceof Etudiant) {
            $type = 'etudiants';
        } else {
            $type = 'personnels';
        }

        // Ajoutez l'ID de l'utilisateur au payload
        $payload['userId'] = $user->getId()??0;
        $payload['type'] = $type;

        $event->setData($payload);
    }
}
