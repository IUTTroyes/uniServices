<?php

namespace DocumentBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;
use DocumentBundle\Repository\DocumentCategoryRepository;
use DocumentBundle\Repository\DocumentRepository;

class DocumentWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentCategoryRepository $categoryRepository
    ) {}

    public function supports(string $code): bool
    {
        return str_starts_with($code, 'document.');
    }

    public function getData(string $code, Personnel $user): array
    {
        return match ($code) {
            'document.recents' => [
                'items' => array_map(
                    fn($doc) => [
                        'id' => $doc->getId(),
                        'title' => $doc->getTitre(),
                        'type' => $doc->getType(),
                        'size' => $doc->getFileSize(),
                        'category' => $doc->getCategory()?->getLibelle() ?? 'Général',
                        'updatedAt' => $doc->getUpdatedAt()?->format('d/m/Y H:i') ?? $doc->getCreatedAt()?->format('d/m/Y H:i'),
                        'author' => $doc->getAuthor(),
                    ],
                    $this->documentRepository->findBy([], ['createdAt' => 'DESC'], 5)
                ),
            ],
            'document.stats' => [
                'totalDocuments' => $this->documentRepository->count([]),
                'totalCategories' => $this->categoryRepository->count([]),
                'favoriteCount' => $this->documentRepository->count(['isFavorite' => true]),
            ],
            default => [],
        };
    }
}
