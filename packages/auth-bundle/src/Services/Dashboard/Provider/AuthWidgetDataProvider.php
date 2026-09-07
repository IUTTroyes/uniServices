<?php

namespace AuthBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use App\Repository\DepartementActualiteRepository;
use App\Repository\Edt\EdtEventRepository;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly StructureDepartementPersonnelRepository $structureDepartementPersonnelRepository,
        private readonly DepartementActualiteRepository $departementActualiteRepository,
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
            'auth.actus_int' => $this->getActusInt($user),
            default => [],
        };
    }

    private function getActusInt(Personnel $user): array
    {
        $departement = $this->structureDepartementPersonnelRepository->findOneBy(['personnel' => $user, 'defaut' => true])->getDepartement();
        $actus = $this->departementActualiteRepository->findBy(['departement' => $departement]);

        foreach ($actus as $key => $actu) {
            $actus[$key] = [
                'id' => $actu->getId(),
                'created' => $actu->getCreated(),
                'updated' => $actu->getUpdated(),
                'libelle' => $actu->getLibelle(),
                'description' => $actu->getDescription(),
                'dateDebut' => $actu->getDateDebut(),
                'dateFin' => $actu->getDateFin(),
                'link' => $actu->getLink(),
                'public' => $actu->getPublic(),
            ];
        }

        return $actus;
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
