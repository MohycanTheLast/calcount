<?php

namespace App\Tests\Api;

use App\Tests\ApiTestCase;

/**
 * Nastavení uživatele: uložení, validace a funkce podle typu účtu.
 */
class UserSettingsTest extends ApiTestCase
{
    private const SETTINGS = [
        'bmrKcal' => 1650.6,
        'avgDailyActivityKcal' => 400,
        'themeMode' => 'dark',
        'aiCommentMode' => 'funny',
        'weightKg' => 72.4,
        'saveWeight' => true,
        'activitySource' => 'synced_or_static',
        'photoGalleryEnabled' => true,
        'hydrationEnabled' => true,
        'waterGoalMl' => 2500,
        'caffeineEnabled' => true,
        'caffeineLimitMg' => 300,
        'macrosEnabled' => true,
    ];

    public function testSaveAndLoad(): void
    {
        $token = $this->jwt($this->createUser(accountType: 'premium'));

        $this->request('POST', '/api/user/settings', self::SETTINGS, $token);
        $this->assertStatus(202);

        $saved = $this->request('GET', '/api/user/settings', token: $token);
        self::assertSame(1651, $saved['bmrKcal']);
        self::assertSame(72, $saved['weightKg']);
        self::assertSame('dark', $saved['themeMode']);
        self::assertSame('synced_or_static', $saved['activitySource']);
        self::assertSame(2500, $saved['waterGoalMl']);
        self::assertTrue($saved['caffeineEnabled']);
        self::assertTrue($saved['macrosEnabled']);
    }

    public function testFreeAccountCannotEnablePaidFeatures(): void
    {
        $token = $this->jwt($this->createUser(accountType: 'free'));

        $this->request('POST', '/api/user/settings', self::SETTINGS, $token);
        $this->assertStatus(202);

        $saved = $this->request('GET', '/api/user/settings', token: $token);
        self::assertFalse($saved['caffeineEnabled']);
        self::assertFalse($saved['macrosEnabled']);
        self::assertTrue($saved['hydrationEnabled']);
    }

    public function testValidation(): void
    {
        $token = $this->jwt($this->createUser());

        $data = $this->request('POST', '/api/user/settings', [...self::SETTINGS, 'themeMode' => 'neon', 'waterGoalMl' => 100], $token);

        $this->assertStatus(422);
        self::assertSame(['themeMode', 'waterGoalMl'], array_column($data['violations'], 'field'));
    }

    public function testNullResetsOptionalValues(): void
    {
        $token = $this->jwt($this->createUser());
        $this->request('POST', '/api/user/settings', self::SETTINGS, $token);

        $this->request('POST', '/api/user/settings', [...self::SETTINGS, 'bmrKcal' => null, 'waterGoalMl' => null], $token);
        $this->assertStatus(202);

        $saved = $this->request('GET', '/api/user/settings', token: $token);
        self::assertNull($saved['bmrKcal']);
        self::assertNull($saved['waterGoalMl']);
    }
}
