<?php

namespace App\Model;

// Odhad AI k jednomu záznamu (POST /api/calculate). Makroživiny v gramech, kofein v mg; null = AI je nevrátila.
final class DietRecord
{
    public function __construct(
        public float $kcal,
        public float $waterInMl,
        public ?string $comment = null,
        public ?int $id = null,
        public ?float $proteinG = null,
        public ?float $carbsG = null,
        public ?float $sugarG = null,
        public ?float $fatG = null,
        public ?float $caffeineMg = null,
    ) {}
}
