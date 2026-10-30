<?php

namespace App\Tests\Support;

use App\Model\DietRecord;
use App\Service\OpenAiClient;

/**
 * Náhrada OpenAiClient v testech (config/services_test.yaml) – vrací pevný odhad, nic neposílá ven.
 */
class FakeOpenAiClient extends OpenAiClient
{
    public const KCAL = 250;
    public const WATER_ML = 200;

    public function estimateNutrition(string $text): DietRecord
    {
        return new DietRecord(
            kcal: self::KCAL,
            waterInMl: self::WATER_ML,
            proteinG: 10.4,
            carbsG: 30,
            sugarG: 5,
            fatG: 8,
            caffeineMg: 0,
        );
    }

    public function analyzeImage(string $base64Image, string $mimeType): string
    {
        return 'Rohlík s máslem';
    }
}
