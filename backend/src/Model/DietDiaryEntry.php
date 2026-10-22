<?php

namespace App\Model;

class DietDiaryEntry
{
    public function __construct(
        public int $id,
        public string $text,
        public float $kcal,
        public float $water,
    ) {}
}
