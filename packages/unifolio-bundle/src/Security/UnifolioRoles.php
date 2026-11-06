<?php

namespace UnifolioBundle\Security;

final class UnifolioRoles
{
    public const REFERENT = 'ROLE_REFERENT_PORTFOLIO';
    public const CORRECTEUR = 'ROLE_PORTFOLIO_CORRECTOR';
    public const PUBLICATION = 'ROLE_PORTFOLIO_PUBLISHER';
    public const ENSEIGNANT = 'ROLE_TEACHER';
    public const SUPER_ADMIN = 'SUPER_ADMIN';

    private function __construct()
    {
    }
}
