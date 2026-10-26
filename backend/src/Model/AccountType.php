<?php

namespace App\Model;

/**
 * Typy účtů a funkce, které k nim patří – na jednom místě pro backend i frontend (/api/user/me → features).
 *
 * free = základ, premium = placené funkce, superior = vše jako premium + do budoucna další
 * (nastavuje se jen přímo v databázi, viz docs/operations.md).
 */
final class AccountType
{
    public const FREE = 'free';
    public const PREMIUM = 'premium';
    public const SUPERIOR = 'superior';
    public const ALL = [self::FREE, self::PREMIUM, self::SUPERIOR];

    // Rozpad záznamů na makroživiny (bílkoviny, sacharidy, cukry, tuky)
    public const FEATURE_MACROS = 'macros';
    // Hlídání kofeinu proti dennímu limitu
    public const FEATURE_CAFFEINE = 'caffeine';

    private const FEATURES = [
        self::FREE => [],
        self::PREMIUM => [self::FEATURE_MACROS, self::FEATURE_CAFFEINE],
        self::SUPERIOR => [self::FEATURE_MACROS, self::FEATURE_CAFFEINE],
    ];

    /**
     * @return list<string>
     */
    public static function features(string $accountType): array
    {
        return self::FEATURES[$accountType] ?? [];
    }

    public static function hasFeature(string $accountType, string $feature): bool
    {
        return in_array($feature, self::features($accountType), true);
    }
}
