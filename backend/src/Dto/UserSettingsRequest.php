<?php

namespace App\Dto;

use App\Entity\UserSettings;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/user/settings – celé nastavení najednou (frontend posílá všechny hodnoty).
 *
 * Volitelné položky (null), které starší verze frontendu neposílá, se nemění. Výjimka: denní cíl vody
 * a limit kofeinu, kde null znamená „výchozí hodnota“.
 * Čísla z formuláře můžou být desetinná – ukládají se zaokrouhlená.
 */
final class UserSettingsRequest
{
    public const THEME_MODES = ['system', 'light', 'dark'];
    public const AI_COMMENT_MODES = ['none', 'brief_health', 'funny', 'praise_or_criticize', 'random'];

    public function __construct(
        #[Assert\Range(notInRangeMessage: 'BMR musí být od {{ min }} do {{ max }} kcal.', min: 0, max: 10000)]
        public readonly ?float $bmrKcal = null,
        #[Assert\Range(notInRangeMessage: 'Průměrná aktivita musí být od {{ min }} do {{ max }} kcal.', min: 0, max: 10000)]
        public readonly ?float $avgDailyActivityKcal = null,
        #[Assert\Choice(choices: self::THEME_MODES, message: 'Neznámý vzhled aplikace.')]
        public readonly string $themeMode = 'system',
        #[Assert\Choice(choices: self::AI_COMMENT_MODES, message: 'Neznámý typ komentáře AI.')]
        public readonly string $aiCommentMode = 'none',
        #[Assert\Range(notInRangeMessage: 'Váha musí být od {{ min }} do {{ max }} kg.', min: 0, max: 500)]
        public readonly ?float $weightKg = null,
        public readonly bool $saveWeight = false,
        #[Assert\Choice(choices: UserSettings::ACTIVITY_SOURCES, message: 'Neznámý zdroj aktivních kalorií.')]
        public readonly ?string $activitySource = null,
        public readonly ?bool $photoGalleryEnabled = null,
        public readonly ?bool $hydrationEnabled = null,
        #[Assert\Range(notInRangeMessage: 'Denní cíl vody musí být {{ min }}–{{ max }} ml.', min: 500, max: 6000)]
        public readonly ?int $waterGoalMl = null,
        public readonly ?bool $caffeineEnabled = null,
        #[Assert\Range(notInRangeMessage: 'Limit kofeinu musí být {{ min }}–{{ max }} mg.', min: 50, max: 1000)]
        public readonly ?int $caffeineLimitMg = null,
        public readonly ?bool $macrosEnabled = null,
    ) {
    }
}
