<?php

namespace FinanceBundle\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use FinanceBundle\Entity\FinanceBonCommande;

class FinancePdfService
{
    private string $gotenbergUrl;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private Environment $twig;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        Environment $twig,
        string $gotenbergUrl = 'http://127.0.0.1:3000'
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->gotenbergUrl = rtrim($gotenbergUrl, '/');
    }

    public function renderHtml(FinanceBonCommande $commande): string
    {
        return $this->twig->render('@Finance/pdf/formulaire_bon_commande.html.twig', [
            'commande' => $commande,
        ]);
    }

    public function generatePdf(FinanceBonCommande $commande): string
    {
        $html = $this->renderHtml($commande);

        try {
            $this->logger->info('Gotenberg: Converting Bon de Commande HTML to PDF', [
                'commande_id' => $commande->getId(),
                'url' => $this->gotenbergUrl,
            ]);

            $formFields = [
                'files' => new DataPart($html, 'index.html', 'text/html'),
            ];
            $formData = new FormDataPart($formFields);

            $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/chromium/convert/html', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException('Gotenberg failed to convert HTML to PDF, status: ' . $response->getStatusCode());
            }

            return $response->getContent();
        } catch (\Exception $e) {
            $this->logger->error('Gotenberg PDF generation error: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la génération PDF : ' . $e->getMessage(), 0, $e);
        }
    }
}
