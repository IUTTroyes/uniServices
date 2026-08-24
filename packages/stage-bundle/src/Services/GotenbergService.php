<?php

namespace StageBundle\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Psr\Log\LoggerInterface;

class GotenbergService
{
    private string $gotenbergUrl;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $gotenbergUrl = 'http://127.0.0.1:3000'
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->gotenbergUrl = rtrim($gotenbergUrl, '/');
    }

    /**
     * Convert HTML content to PDF binary stream using Gotenberg Chromium
     *
     * @param string $html
     * @return string Binary PDF content
     */
    public function convertHtmlToPdf(string $html): string
    {
        try {
            $this->logger->info('Gotenberg: Starting HTML to PDF conversion', [
                'url' => $this->gotenbergUrl,
                'html_length' => strlen($html)
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
                $this->logger->error('Gotenberg returned an error status code', [
                    'status' => $response->getStatusCode(),
                    'response' => $response->getContent(false)
                ]);
                throw new \RuntimeException('Gotenberg failed to convert HTML to PDF, status: ' . $response->getStatusCode());
            }

            return $response->getContent();
        } catch (\Exception $e) {
            $this->logger->error('Exception while calling Gotenberg service', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to convert PDF via Gotenberg: ' . $e->getMessage(), 0, $e);
        }
    }
}
