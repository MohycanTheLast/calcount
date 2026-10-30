<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * PATCH /api/diary-entries/{id} – ruční oprava odhadu. Neposlaná hodnota se nemění, desetinná se zaokrouhlí.
 */
final class UpdateDiaryEntryRequest
{
    public function __construct(
        #[Assert\Range(notInRangeMessage: 'Kalorie musí být od {{ min }} do {{ max }}.', min: 0, max: 20000)]
        public readonly ?float $kcal = null,
        #[Assert\Range(notInRangeMessage: 'Voda musí být od {{ min }} do {{ max }} ml.', min: 0, max: 20000)]
        public readonly ?float $waterInMl = null,
    ) {
    }
}
