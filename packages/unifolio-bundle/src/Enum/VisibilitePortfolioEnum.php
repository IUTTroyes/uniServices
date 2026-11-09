<?php

namespace UnifolioBundle\Enum;

use App\Enum\BadgeEnumInterface;

enum VisibilitePortfolioEnum: string implements BadgeEnumInterface
{
    case PRIVE = 'prive';
    case LIEN_SECRET = 'lien_secret';
    case ETABLISSEMENT = 'etablissement';
    case PUBLIC = 'public';

    public function getLibelle(): string
    {
        return match ($this) {
            self::PRIVE => 'Privé',
            self::LIEN_SECRET => 'Accessible par lien secret',
            self::ETABLISSEMENT => 'Établissement',
            self::PUBLIC => 'Public',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PRIVE => 'secondary',
            self::LIEN_SECRET => 'info',
            self::ETABLISSEMENT => 'warn',
            self::PUBLIC => 'success',
        };
    }

    /** Le rendu statique peut être servi directement par le serveur web (sans contrôle d'accès). */
    public function isServableDirectement(): bool
    {
        return $this === self::PUBLIC;
    }

    public function requiertAuthentification(): bool
    {
        return \in_array($this, [self::PRIVE, self::ETABLISSEMENT], true);
    }
}
