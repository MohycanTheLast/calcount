<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/tokens – nový osobní API token. Prázdný název = výchozí („iPhone – Zkratky“).
 */
final class CreateTokenRequest
{
    public function __construct(
        #[Assert\Length(max: 100, maxMessage: 'Název může mít nejvýš {{ limit }} znaků.', normalizer: 'trim')]
        public readonly string $name = '',
    ) {
    }
}
