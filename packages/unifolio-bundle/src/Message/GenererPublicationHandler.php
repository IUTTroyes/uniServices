<?php

namespace UnifolioBundle\Message;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use UnifolioBundle\Repository\PortfolioPublicationRepository;
use UnifolioBundle\Service\Publication\PublicationManager;

#[AsMessageHandler]
final class GenererPublicationHandler
{
    public function __construct(
        private readonly PortfolioPublicationRepository $publications,
        private readonly PublicationManager $manager,
    ) {
    }

    public function __invoke(GenererPublication $message): void
    {
        $publication = $this->publications->findOneByUuid($message->publicationUuid);
        if ($publication !== null) {
            $this->manager->generer($publication);
        }
    }
}
