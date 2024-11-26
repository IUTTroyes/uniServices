<?php

namespace App\EventListener;

use App\Entity\Structure\StructureEtudiant;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $user = $event->getUser();
        $payload = $event->getData();

        if ($user instanceof StructureEtudiant) {
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
