<?php

namespace App\Controller;

use App\Entity\StructureEtudiant;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $dispatcher,
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Générez un token JWT pour l'utilisateur authentifié
        $token = $jwtManager->create($user);

        // ajouter l'ID de l'utilisateur au token
        $dispatcher->dispatch(new JWTCreatedEvent(['token' => $token], $user));

        return new JsonResponse([
            'token' => $token
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
