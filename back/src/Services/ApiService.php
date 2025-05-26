<?php

namespace App\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected string              $appEnv
    )
    {
    }

    public function request(string $method, string $url, array $options = []): array
    {
        // Désactiver la vérification SSL en environnement de développement
        if ($this->appEnv === 'dev') {
            $options['verify_peer'] = false;
            $options['verify_host'] = false;
        }

        $response = $this->httpClient->request($method, $url, $options);

        $content = $response->getContent();
dump($url);
        $decode = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error decoding JSON response: ' . json_last_error_msg());
        }

        return $decode;
    }
}
