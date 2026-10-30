<?php

namespace App\Tests\Api;

use App\Entity\ActivityEnergy;
use App\Entity\UserSettings;
use App\Tests\ApiTestCase;

/**
 * Souhrny dní (GET /api/days, DaySummaryService): příjem, výdej podle zdroje aktivních kalorií a bilance.
 */
class DaysTest extends ApiTestCase
{
    public function testDaysWithoutBmrHaveNoBalance(): void
    {
        $user = $this->createUser();
        $this->addEntry($user, '2026-10-01', 500, 250);
        $this->addEntry($user, '2026-10-01', 300);
        $this->addEntry($user, '2026-10-03', 100);

        $days = $this->request('GET', '/api/days?from=2026-10-01&to=2026-10-03', token: $this->jwt($user));

        $this->assertStatus(200);
        self::assertSame(['2026-10-01', '2026-10-02', '2026-10-03'], array_column($days, 'date'));
        self::assertSame([800, 0, 100], array_column($days, 'kcal'));
        self::assertSame([2, 0, 1], array_column($days, 'entryCount'));
        self::assertSame(250, $days[0]['waterMl']);
        self::assertNull($days[0]['expenditureKcal']);
        self::assertNull($days[0]['balanceKcal']);
    }

    public function testStaticActivity(): void
    {
        $user = $this->createUser();
        $this->configureSettings($user, ['BmrKcal' => 1600, 'AvgDailyActivityKcal' => 400]);
        $this->addEntry($user, '2026-10-01', 1500);

        $day = $this->request('GET', '/api/days?from=2026-10-01&to=2026-10-01', token: $this->jwt($user))[0];

        self::assertSame(400, $day['activeKcal']);
        self::assertSame('static', $day['activeSource']);
        self::assertSame(2000, $day['expenditureKcal']);
        self::assertSame(-500, $day['balanceKcal']);
    }

    public function testSyncedOrStaticFallsBackToAverage(): void
    {
        $user = $this->createUser();
        $this->configureSettings($user, [
            'BmrKcal' => 1600,
            'AvgDailyActivityKcal' => 400,
            'ActivitySource' => UserSettings::ACTIVITY_SOURCE_SYNCED_OR_STATIC,
        ]);
        $this->addActivity($user, '2026-10-01', ActivityEnergy::SOURCE_APPLE_HEALTH, 550);
        $this->addActivity($user, '2026-10-01', ActivityEnergy::SOURCE_STRAVA, 300);

        $days = $this->request('GET', '/api/days?from=2026-10-01&to=2026-10-02', token: $this->jwt($user));

        // Víc synchronizovaných zdrojů se nesčítá – bere se maximum
        self::assertSame([550, 400], array_column($days, 'activeKcal'));
        self::assertSame(['apple_health', 'static'], array_column($days, 'activeSource'));
        self::assertSame([2150, 2000], array_column($days, 'expenditureKcal'));
    }

    public function testSyncedOnlyIgnoresAverage(): void
    {
        $user = $this->createUser();
        $this->configureSettings($user, [
            'BmrKcal' => 1600,
            'AvgDailyActivityKcal' => 400,
            'ActivitySource' => UserSettings::ACTIVITY_SOURCE_SYNCED,
        ]);

        $day = $this->request('GET', '/api/days?from=2026-10-01&to=2026-10-01', token: $this->jwt($user))[0];

        self::assertNull($day['activeKcal']);
        self::assertSame('none', $day['activeSource']);
        self::assertSame(1600, $day['expenditureKcal']);
    }

    public function testManualValueWins(): void
    {
        $user = $this->createUser();
        $this->configureSettings($user, [
            'BmrKcal' => 1600,
            'ActivitySource' => UserSettings::ACTIVITY_SOURCE_SYNCED_OR_STATIC,
        ]);
        $this->addActivity($user, '2026-10-01', ActivityEnergy::SOURCE_APPLE_HEALTH, 550);
        $this->addActivity($user, '2026-10-01', ActivityEnergy::SOURCE_MANUAL, 120);

        $day = $this->request('GET', '/api/days?from=2026-10-01&to=2026-10-01', token: $this->jwt($user))[0];

        self::assertSame(120, $day['activeKcal']);
        self::assertSame('manual', $day['activeSource']);
    }

    public function testManualActivityEndpoint(): void
    {
        $user = $this->createUser();
        $this->configureSettings($user, ['BmrKcal' => 1600, 'AvgDailyActivityKcal' => 400]);
        $token = $this->jwt($user);
        $date = date('Y-m-d');

        $day = $this->request('PUT', "/api/activity/manual/$date", ['activeKcal' => 250.4], $token);
        $this->assertStatus(200);
        self::assertSame(250, $day['activeKcal']);
        self::assertSame('manual', $day['activeSource']);

        $data = $this->request('PUT', "/api/activity/manual/$date", ['activeKcal' => 20000], $token);
        $this->assertStatus(422);
        self::assertSame('activeKcal', $data['violations'][0]['field']);

        $day = $this->request('DELETE', "/api/activity/manual/$date", token: $token);
        $this->assertStatus(200);
        self::assertSame('static', $day['activeSource']);
    }

    public function testOtherUsersEntriesAreNotCounted(): void
    {
        $user = $this->createUser('jana@example.com');
        $other = $this->createUser('petr@example.com');
        $this->addEntry($other, '2026-10-01', 900);

        $day = $this->request('GET', '/api/days?from=2026-10-01&to=2026-10-01', token: $this->jwt($user))[0];

        self::assertSame(0, $day['kcal']);
    }

    public function testInvalidRange(): void
    {
        $token = $this->jwt($this->createUser());

        self::assertArrayHasKey('error', $this->request('GET', '/api/days?from=2026-10-05&to=2026-10-01', token: $token));
        $this->assertStatus(400);

        $this->request('GET', '/api/days?from=2025-01-01&to=2026-10-01', token: $token);
        $this->assertStatus(400);

        $this->request('GET', '/api/days?from=2026-02-30', token: $token);
        $this->assertStatus(400);
    }
}
