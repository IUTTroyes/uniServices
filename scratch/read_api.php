<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

require_once __DIR__ . '/../back/vendor/autoload.php';
(new Dotenv())->bootEnv(__DIR__ . '/../back/.env');

$client = HttpClient::create();
$response = $client->request('GET', 'http://127.0.0.1:8000/api/stage_soutenances', [
    'headers' => [
        'Accept' => 'application/json'
    ]
]);

$data = json_decode($response->getContent(), true);
echo json_encode(array_slice($data, 0, 2), JSON_PRETTY_PRINT) . "\n";
