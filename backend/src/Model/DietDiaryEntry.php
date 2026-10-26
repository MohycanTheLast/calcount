<?php

namespace App\Model;

// Záznam v detailu dne (GET /api/diary-day). Makroživiny a kofein null u záznamů z doby před jejich odhadem.
class DietDiaryEntry
{
    public function __construct(
        public int $id,
        public string $text,
        public float $kcal,
        public float $water,
        public ?int $proteinG = null,
        public ?int $carbsG = null,
        public ?int $sugarG = null,
        public ?int $fatG = null,
        public ?int $caffeineMg = null,
    ) {}
}
