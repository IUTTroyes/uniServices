<?php

namespace App\EventListener;

use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\Cookie;

class AuthenticationSuccessListener
{
    public function __construct(
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private int $refreshTokenTtl = 1209600 // 14 jours par défaut
    ) {
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $response = $event->getResponse();

        // Générer le refresh token
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
            $user,
            $this->refreshTokenTtl
        );

        // Sauvegarder le refresh token
        $this->refreshTokenManager->save($refreshToken);

        // Créer le cookie HTTP-only pour le refresh token
        $refreshTokenCookie = Cookie::create('refresh_token')
            ->withValue($refreshToken->getRefreshToken())
            ->withExpires(new \DateTime('+' . $this->refreshTokenTtl . ' seconds'))
            ->withPath('/')
            ->withSecure(false) // Mettre true en production avec HTTPS
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($refreshTokenCookie);

        // Récupérer le token JWT de la réponse
        $data = $event->getData();
        $token = $data['token'] ?? null;

        if ($token) {
            // Créer le cookie HTTP-only pour le JWT
            $jwtCookie = Cookie::create('BEARER')
                ->withValue($token)
                ->withExpires(new \DateTime('+15 minutes'))
                ->withPath('/')
                ->withSecure(false) // Mettre true en production avec HTTPS
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX);

            $response->headers->setCookie($jwtCookie);
        }

        // Retourner les informations utilisateur
        // Le token est envoyé dans le body pour compatibilité avec l'existant
        // mais le frontend devrait utiliser les cookies
        $event->setData([
            'token' => $token,
            'message' => 'Authentication successful',
        ]);
    }
}


