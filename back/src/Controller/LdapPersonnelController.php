<?php

namespace App\Controller;

use App\Repository\PersonnelRepository;
use App\Security\PersonnelVoter;
use App\Service\Ldap\LdapPersonnelService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LdapPersonnelController extends AbstractController
{
    /**
     * Recherche d'un personnel dans le LDAP par code RH, utilisée avant la création d'un personnel.
     * 404 : introuvable ; 503 : LDAP non configuré ou injoignable (le front bascule alors en saisie libre).
     */
    #[Route('/api/ldap/personnel/{numeroHarpege}', name: 'api_ldap_personnel', requirements: ['numeroHarpege' => '\d{1,20}'], methods: ['GET'])]
    #[IsGranted(PersonnelVoter::CAN_EDIT_PERSONNEL)]
    public function recherche(
        string $numeroHarpege,
        LdapPersonnelService $ldap,
        PersonnelRepository $personnels,
        LoggerInterface $logger,
    ): JsonResponse {
        if ($personnels->findOneBy(['numeroHarpege' => (int) $numeroHarpege])) {
            return new JsonResponse(['message' => 'Un personnel existe déjà avec ce code RH.'], Response::HTTP_CONFLICT);
        }

        if (!$ldap->isConfigured()) {
            return new JsonResponse(['message' => 'Le LDAP n\'est pas configuré.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $info = $ldap->getInfoPersonnel($numeroHarpege);
        } catch (\Throwable $e) {
            $logger->error('Erreur LDAP lors de la recherche du code RH '.$numeroHarpege.' : '.$e->getMessage());

            return new JsonResponse(['message' => 'Le LDAP est injoignable.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (null === $info) {
            return new JsonResponse(['message' => 'Aucun personnel trouvé dans le LDAP pour ce code RH.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($info);
    }
}
