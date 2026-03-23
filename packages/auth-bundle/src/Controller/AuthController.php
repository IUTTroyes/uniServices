<?php

namespace AuthBundle\Controller;

use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag
    ) {
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Logged out successfully']);

        // Récupérer et invalider le refresh token
        $refreshToken = $request->cookies->get('refresh_token');
        if ($refreshToken) {
            $token = $this->refreshTokenManager->get($refreshToken);
            if ($token) {
                $this->entityManager->remove($token);
                $this->entityManager->flush();
            }
        }

        $secure = $this->parameterBag->get('JWT_COOKIE_SECURE') === 'true' || $this->parameterBag->get('JWT_COOKIE_SECURE') === true;

        // Supprimer les cookies
        $response->headers->setCookie(
            Cookie::create('BEARER')
                ->withValue('')
                ->withExpires(new \DateTime('-1 hour'))
                ->withPath('/')
                ->withSecure($secure)
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        $response->headers->setCookie(
            Cookie::create('refresh_token')
                ->withValue('')
                ->withExpires(new \DateTime('-1 hour'))
                ->withPath('/')
                ->withSecure($secure)
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        return $response;
    }

    #[Route('/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['authenticated' => false], Response::HTTP_UNAUTHORIZED);
        }

        $type = $user instanceof Etudiant ? 'etudiants' : 'personnels';

        return new JsonResponse([
            'authenticated' => true,
            'userId' => $user->getId(),
            'type' => $type,
            'username' => $user->getUserIdentifier(),
        ]);
    }

    #[Route('/auth/check', name: 'api_auth_check', methods: ['GET'])]
    public function checkAuth(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['authenticated' => false], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'authenticated' => true,
            'user' => [
                'username' => $user->getUserIdentifier(),
            ]
        ]);
    }
}


