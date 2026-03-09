<?php

namespace App\ApiDto\Security;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordDto
{
    #[Assert\NotBlank(message: 'Le token est requis')]
    public string $token;

    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/[a-z]/',
        message: 'Le mot de passe doit contenir au moins une lettre minuscule'
    )]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'Le mot de passe doit contenir au moins une lettre majuscule'
    )]
    #[Assert\Regex(
        pattern: '/[0-9]/',
        message: 'Le mot de passe doit contenir au moins un chiffre'
    )]
    #[Assert\Regex(
        pattern: '/[!@#$%^&*(),.?":{}|<>]/',
        message: 'Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*(),.?":{}|<>)'
    )]
    public string $password;
}

