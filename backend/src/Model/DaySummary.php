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
        // Součty makroživin (g) a kofeinu (mg) ze záznamů, které je mají odhadnuté
        public int $proteinG = 0,
        public int $carbsG = 0,
        public int $sugarG = 0,
        public int $fatG = 0,
        public int $caffeineMg = 0,
        // Kolik záznamů dne má odhad makroživin a kofeinu (starší záznamy ho nemají)
        public int $nutrientsEntryCount = 0,
    ) {}
}
