<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RssController extends AbstractController
{
    #[Route('/api/actualites', name: 'app_rss_actus')]
    public function index(): Response
    {
        // faire une requête vers : https://www.univ-reims.fr/iut-troyes/service/rss/getRss.php?type=news
        $actus = simplexml_load_file('https://www.univ-reims.fr/iut-troyes/service/rss/getRss.php?type=news');
        $data = [];
        $count = 0;
        foreach ($actus->channel->item as $actu) {
            if ($count >= 4) break;
            $data[] = [
                'title' => (string) $actu->title,
                'description' => html_entity_decode(mb_convert_encoding((string)$actu->description, 'UTF-8', 'ISO-8859-1')),
                'link' => (string) $actu->link,
                'pubDate' => (string) $actu->pubDate,
                'image' => (string) $actu->enclosure['url'],
            ];
            $count++;
        }

        return $this->json($data);
    }
}
