<?php

namespace AuthBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;
use App\Repository\Edt\EdtEventRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function supports(string $code): bool
    {
        return str_starts_with($code, 'auth.');
    }

    public function getData(string $code, Personnel $user): array
    {
        return match ($code) {
            'auth.actus_ext' => $this->getActusExt(),
            'auth.actus_int' => $this->getActusInt(),
            default => [],
        };
    }

    private function getActusInt(): array
    {
        // Implementation for getting internal news data
        return [
            [
                'title' => 'Internal News',
                'description' => 'This is a placeholder for internal news data.',
                'link' => '#',
                'pubDate' => (new \DateTime())->format('D, d M Y H:i:s O'),
                'image' => '',
            ],
            [
                'title' => 'Another Internal News',
                'description' => 'This is another placeholder for internal news data.',
                'link' => '#',
                'pubDate' => (new \DateTime())->format('D, d M Y H:i:s O'),
                'image' => '',
            ]
        ];
    }

    private function getActusExt(): array
    {
        // récupérer l'url depuis .env
        $actus_url=$_ENV['URL_ACTUS'];
        $actus = $this->loadRss($actus_url);
        $data = [];
        if ($actus && isset($actus->channel->item)) {
            $count = 0;
            foreach ($actus->channel->item as $actu) {
                if ($count >= 4) break;
                $data[] = [
                    'title' => (string) $actu->title,
                    'description' => html_entity_decode($actu->description),
                    'link' => (string) $actu->link,
                    'pubDate' => (string) $actu->pubDate,
                    'image' => isset($actu->enclosure['url']) ? (string) $actu->enclosure['url'] : '',
                ];
                $count++;
            }
        }

        // En cas d'erreur réseau/SSL, on renvoie un tableau vide pour ne pas casser le front
        return $data;
    }

    private function loadRss(string $url): ?\SimpleXMLElement
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'User-Agent' => 'uniServices/1.0',
                ],
            ]);

            $content = $response->getContent();
        } catch (\Exception $e) {
            return null;
        }

        \libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            return null;
        }
        return $xml;
    }
}
