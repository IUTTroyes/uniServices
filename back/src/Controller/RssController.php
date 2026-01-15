<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RssController extends AbstractController
{
    private function loadRss(string $url): ?\SimpleXMLElement
    {
        $context = stream_context_create([
            'ssl' => [
                // Attention: désactive la vérification SSL (workaround temporaire)
                // Idéalement, corriger la chaîne de certificats du serveur ou installer un bundle CA à jour.
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => "User-Agent: uniServices/1.0\r\n",
            ],
        ]);

        \libxml_use_internal_errors(true);
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return null;
        }
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
