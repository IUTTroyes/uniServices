<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RssController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
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

    #[Route('/api/actualites', name: 'app_rss_actus')]
    public function getActus(): Response
    {
        $actus = $this->loadRss('https://www.univ-reims.fr/iut-troyes/service/rss/getRss.php?type=news');
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
        return $this->json($data);
    }

    #[Route('/api/agenda', name: 'app_rss_events')]
    public function getEvents(): Response
    {
        $events = $this->loadRss('https://www.univ-reims.fr/iut-troyes/service/rss/getRss.php?type=event');
        $data = [];
        if ($events && isset($events->channel->item)) {
            $count = 0;
            foreach ($events->channel->item as $event) {
                if ($count >= 4) break;
                $data[] = [
                    'title' => (string) $event->title,
                    'description' => html_entity_decode($event->description),
                    'link' => (string) $event->link,
                    'pubDate' => (string) $event->pubDate,
                    'image' => isset($event->enclosure['url']) ? (string) $event->enclosure['url'] : '',
                ];
                $count++;
            }
        }

        // En cas d'erreur réseau/SSL, on renvoie un tableau vide pour ne pas casser le front
        return $this->json($data);
    }
}
