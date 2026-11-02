<?php

namespace MessageBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\DashboardContext;
use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use MessageBundle\Entity\MessageDestinataire;
use MessageBundle\Repository\MessageDestinataireRepository;

class MessageWidgetDataProvider implements WidgetDataProviderInterface
{
    public function __construct(
        private readonly MessageDestinataireRepository $destinataireRepository,
    ) {
    }

    public function supports(string $code): bool
    {
        return $code === 'message.recent';
    }

    public function getData(string $code, Personnel|Etudiant $user, ?DashboardContext $context = null): array
    {
        if ($code !== 'message.recent') {
            return [];
        }

        $destinataires = $this->destinataireRepository->findForUser(
            $user,
            ['status' => 'all'],
            5
        );

        $unreadCount = $this->destinataireRepository->countUnreadForUser($user);

        $messages = array_map(function (MessageDestinataire $d) {
            $msg = $d->getMessage();
            $exp = $msg?->getExpediteur();

            return [
                'id' => $msg?->getId(),
                'sujet' => $msg?->getSujet(),
                'expediteur' => $exp ? sprintf('%s %s', $exp->getPrenom(), $exp->getNom()) : 'Expéditeur inconnu',
                'prioritaire' => $msg?->isPrioritaire() ?? false,
                'createdAt' => $msg?->getCreatedAt()?->format('d/m H:i'),
                'isLu' => $d->isLu(),
            ];
        }, $destinataires);

        return [
            'unreadCount' => $unreadCount,
            'messages' => $messages,
        ];
    }
}

