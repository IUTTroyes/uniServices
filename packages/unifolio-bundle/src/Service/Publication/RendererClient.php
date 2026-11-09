<?php

namespace UnifolioBundle\Service\Publication;

use Symfony\Component\Process\Process;

/** Appelle le service de rendu Node (D2) : instantané JSON sur stdin, pages HTML en JSON sur stdout. */
class RendererClient
{
    public function __construct(
        private readonly string $unifolioRendererDir,
        private readonly string $unifolioRendererNode,
        private readonly int $unifolioRendererTimeout,
    ) {
    }

    public function disponible(): bool
    {
        return is_file($this->script()) && is_file($this->unifolioRendererDir.'/dist/'.PublicationStorage::FEUILLE);
    }

    /**
     * @param array<string, mixed> $instantane
     *
     * @return array<string, string> fichier → HTML
     */
    public function rendre(array $instantane): array
    {
        if (!$this->disponible()) {
            throw new \RuntimeException('Le service de rendu n\'est pas construit (npm run build:unifolio-renderer).');
        }
        $process = new Process([$this->unifolioRendererNode, $this->script()], null, ['NODE_ENV' => 'production']);
        $process->setInput(json_encode($instantane, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE));
        $process->setTimeout($this->unifolioRendererTimeout);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Échec du rendu : '.mb_substr(trim($process->getErrorOutput()) ?: 'erreur inconnue', 0, 600));
        }
        try {
            $pages = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \RuntimeException('Le service de rendu a renvoyé une réponse illisible.');
        }
        if (!\is_array($pages) || $pages === []) {
            throw new \RuntimeException('Le service de rendu n\'a produit aucune page.');
        }

        return array_map('strval', $pages);
    }

    private function script(): string
    {
        return $this->unifolioRendererDir.'/dist/cli.mjs';
    }
}
