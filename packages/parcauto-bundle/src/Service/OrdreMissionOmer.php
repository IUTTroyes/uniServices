<?php

declare(strict_types=1);

namespace ParcautoBundle\Service;

use ParcautoBundle\Entity\Reservation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OrdreMissionOmer
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(default::PARCAUTO_OMER_URL)%')]
        private ?string $url,
        #[Autowire('%env(default::PARCAUTO_OMER_TOKEN)%')]
        private ?string $jeton,
    ) {
    }

    public function verifier(Reservation $reservation): bool
    {
        if (!$this->url) {
            return false;
        }
        if (parse_url($this->url, PHP_URL_SCHEME) !== 'https' || !$this->jeton) {
            throw new \DomainException('La connexion OMER doit être configurée avec une URL HTTPS et un jeton.');
        }

        try {
            $reponse = $this->client->request('POST', $this->url, [
                'auth_bearer' => $this->jeton,
                'max_redirects' => 0,
                'timeout' => 4,
                'max_duration' => 6,
                'json' => [
                    'reference' => $reservation->ordreMission,
                    'conducteur' => $reservation->conducteur->getUserIdentifier(),
                    'debut' => $reservation->debut->format(DATE_ATOM),
                    'fin' => $reservation->fin->format(DATE_ATOM),
                ],
            ])->toArray();
        } catch (ExceptionInterface) {
            throw new \DomainException('La vérification OMER est momentanément indisponible.');
        }

        if (($reponse['valide'] ?? null) !== true || !in_array($reponse['type'] ?? null, ['permanent', 'ponctuel'], true)) {
            throw new \DomainException('OMER ne confirme pas un ordre de mission valide pour ce conducteur et ce créneau.');
        }

        return true;
    }
}
