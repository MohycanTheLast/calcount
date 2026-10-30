<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/register – registrace e-mailem a heslem.
 */
final class RegisterRequest
{
    public const PASSWORD_MIN_LENGTH = 8;
    // Hashování extrémně dlouhého hesla zbytečně zatěžuje server
    public const PASSWORD_MAX_LENGTH = 4096;

    public function __construct(
        #[Assert\NotBlank(message: 'Zadej platný e-mail.', normalizer: 'trim')]
        #[Assert\Length(max: 180, maxMessage: 'Zadej platný e-mail.', normalizer: 'trim')]
        #[Assert\Email(message: 'Zadej platný e-mail.', normalizer: 'trim')]
        public readonly string $email = '',
        #[Assert\Length(
            min: self::PASSWORD_MIN_LENGTH,
            max: self::PASSWORD_MAX_LENGTH,
            minMessage: 'Heslo musí mít aspoň {{ limit }} znaků.',
            maxMessage: 'Heslo je příliš dlouhé.',
        )]
        public readonly string $password = '',
        #[Assert\IsTrue(message: 'Pro registraci je potřeba souhlasit s podmínkami užívání.')]
        public readonly bool $termsAccepted = false,
    ) {
    }

    // E-mail se ukládá malými písmeny bez mezer okolo (v DB je unikátní)
    public function getNormalizedEmail(): string
    {
        return mb_strtolower(trim($this->email));
    }
}
