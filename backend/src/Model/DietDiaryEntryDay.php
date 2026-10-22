<?php

namespace App\Model;

class DietDiaryEntryDay
{
    public function __construct(
        public string $date,
        public float $kcal,
        public float $kcalBurned,
        public float $water,
    ) {}
}
