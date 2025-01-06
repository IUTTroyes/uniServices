<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RssController extends AbstractController
{
    #[Route('/api/actualites', name: 'app_rss_actus')]
    public function getActus(): Response
    {
        $actus = simplexml_load_file('https://www.univ-reims.fr/iut-troyes/service/rss/getRss.php?type=news');
        $data = [];
        $count = 0;
        foreach ($actus->channel->item as $actu) {
            if ($count >= 4) break;
            $data[] = [
                'title' => (string) $actu->title,
                'description' => html_entity_decode($actu->description),
                'link' => (string) $actu->link,
                'pubDate' => (string) $actu->pubDate,
                'image' => (string) $actu->enclosure['url'],
            ];
            $count++;
        }

        return $this->json($data);
    }

    #[Route('/api/agenda', name: 'app_rss_events')]
    public function getEvents(): Response
    {
        $events = simplexml_load_file('https://www.univ-reims.fr/iut-troyes/service/rss/getRss.php?type=event');
        $data = [];
        $count = 0;
        foreach ($events->channel->item as $event) {
            if ($count >= 4) break;
            $data[] = [
                'title' => (string) $event->title,
                'description' => html_entity_decode($event->description),
                'link' => (string) $event->link,
                'pubDate' => (string) $event->pubDate,
                'image' => (string) $event->enclosure['url'],
            ];
            $count++;
        }

        return $this->json($data);
    }
}
