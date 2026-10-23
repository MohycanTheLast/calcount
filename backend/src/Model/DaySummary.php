<?php

namespace App\Model;

/**
 * Souhrn jednoho dne pro /api/days (Deník, kalendář, statistiky).
 */
final class DaySummary
{
    public function __construct(
        public string $date, // Y-m-d
        public int $kcal, // příjem
        public int $waterMl,
        public int $entryCount,
        public ?int $bmrKcal,
        public ?int $activeKcal,
        public string $activeSource, // static | manual | apple_health | strava | none
        public ?int $expenditureKcal, // BMR + aktivní kalorie; null bez vyplněného BMR
        public ?int $balanceKcal, // příjem − výdej; záporné = deficit, kladné = přebytek
    ) {}
}
