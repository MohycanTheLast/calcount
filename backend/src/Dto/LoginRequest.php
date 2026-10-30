<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/login – přihlášení e-mailem a heslem.
 */
final class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Zadej e-mail.')]
        public readonly string $email = '',
        #[Assert\NotBlank(message: 'Zadej heslo.')]
        public readonly string $password = '',
    ) {
    }
}
