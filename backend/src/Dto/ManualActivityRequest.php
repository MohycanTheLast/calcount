<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * PUT /api/activity/manual/{date} – ruční hodnota aktivních kalorií dne (desetinné číslo se zaokrouhlí).
 */
final class ManualActivityRequest
{
    public const MAX_ACTIVE_KCAL = 10000;

    public function __construct(
        #[Assert\NotNull(message: 'Zadej aktivní kalorie.')]
        #[Assert\Range(notInRangeMessage: 'Aktivní kalorie musí být číslo od {{ min }} do {{ max }}.', min: 0, max: self::MAX_ACTIVE_KCAL)]
        public readonly ?float $activeKcal = null,
    ) {
    }
}
