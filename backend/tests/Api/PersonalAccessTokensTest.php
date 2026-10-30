<?php

namespace App\Tests\Api;

use App\Entity\ActivityEnergy;
use App\Entity\UserSettings;
use App\Tests\ApiTestCase;

/**
 * Osobní API tokeny (iOS Zkratky): smí jen zapisovat aktivní kalorie, jdou odvolat, spravuje je jen JWT.
 */
class PersonalAccessTokensTest extends ApiTestCase
{
    public function testCreateTokenAndSyncActivity(): void
    {
        $user = $this->createUser();
        $jwt = $this->jwt($user);

        $created = $this->request('POST', '/api/tokens', ['name' => 'Můj iPhone'], $jwt);

        $this->assertStatus(201);
        self::assertStringStartsWith('cc_pat_', $created['token']);
        self::assertSame('Můj iPhone', $created['name']);
        // První token přepne zdroj aktivních kalorií, aby se synchronizovaná data hned projevila
        self::assertSame(UserSettings::ACTIVITY_SOURCE_SYNCED_OR_STATIC, $created['activitySource']);

        $today = date('Y-m-d');
        $data = $this->request('POST', '/api/activity', ['date' => $today, 'activeKcal' => '1 512,4 kcal'], $created['token']);

        $this->assertStatus(200);
        self::assertSame(1, $data['saved']);
        self::assertSame(1512, $data['days'][0]['activeKcal']);

        // Opakované odeslání stejný den přepíše, nesčítá
        $this->request('POST', '/api/activity', [['date' => $today, 'activeKcal' => 600]], $created['token']);
        $this->assertStatus(200);
        $records = $this->em()->getRepository(ActivityEnergy::class)->findBy(['source' => ActivityEnergy::SOURCE_APPLE_HEALTH]);
        self::assertCount(1, $records);
        self::assertSame(600, $records[0]->getActiveKcal());

        $tokens = $this->request('GET', '/api/tokens', token: $jwt);
        self::assertCount(1, $tokens);
        self::assertNotNull($tokens[0]['lastUsedAt']);
        self::assertArrayNotHasKey('token', $tokens[0]);
    }

    public function testEmptyBodyUsesDefaultName(): void
    {
        $this->client->request('POST', '/api/tokens', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt($this->createUser())]);

        $this->assertStatus(201);
        self::assertSame('iPhone – Zkratky', json_decode($this->client->getResponse()->getContent(), true)['name']);
    }

    public function testTokenCannotAccessOtherEndpoints(): void
    {
        $jwt = $this->jwt($this->createUser());
        $pat = $this->request('POST', '/api/tokens', [], $jwt)['token'];

        foreach ([['GET', '/api/days'], ['GET', '/api/user/me'], ['GET', '/api/tokens'], ['POST', '/api/tokens'], ['POST', '/api/calculate']] as [$method, $uri]) {
            $data = $this->request($method, $uri, ['food' => 'x'], $pat);
            $this->assertStatus(403);
            self::assertSame(['error' => 'Forbidden'], $data, "$method $uri");
        }
    }

    public function testRevokedTokenIsRejected(): void
    {
        $jwt = $this->jwt($this->createUser());
        $created = $this->request('POST', '/api/tokens', [], $jwt);

        $this->request('DELETE', '/api/tokens/'.$created['id'], token: $jwt);
        $this->assertStatus(204);

        $data = $this->request('POST', '/api/activity', ['date' => date('Y-m-d'), 'activeKcal' => 100], $created['token']);
        $this->assertStatus(401);
        self::assertSame(['error' => 'Invalid token'], $data);
        self::assertSame([], $this->request('GET', '/api/tokens', token: $jwt));
    }

    public function testCannotRevokeForeignToken(): void
    {
        $created = $this->request('POST', '/api/tokens', [], $this->jwt($this->createUser('jana@example.com')));

        $this->request('DELETE', '/api/tokens/'.$created['id'], token: $this->jwt($this->createUser('petr@example.com')));

        $this->assertStatus(404);
    }

    public function testTokenLimitAndNameValidation(): void
    {
        $jwt = $this->jwt($this->createUser());

        $data = $this->request('POST', '/api/tokens', ['name' => str_repeat('a', 101)], $jwt);
        $this->assertStatus(422);
        self::assertSame('name', $data['violations'][0]['field']);

        for ($i = 0; $i < 5; $i++) {
            $this->request('POST', '/api/tokens', [], $jwt);
            $this->assertStatus(201);
        }

        $this->request('POST', '/api/tokens', [], $jwt);
        $this->assertStatus(400);
    }

    public function testActivitySyncValidation(): void
    {
        $pat = $this->request('POST', '/api/tokens', [], $this->jwt($this->createUser()))['token'];

        $tooOld = (new \DateTimeImmutable('-40 days'))->format('Y-m-d');
        self::assertArrayHasKey('error', $this->request('POST', '/api/activity', ['date' => $tooOld, 'activeKcal' => 100], $pat));
        $this->assertStatus(400);

        $this->request('POST', '/api/activity', ['date' => date('Y-m-d'), 'activeKcal' => -1], $pat);
        $this->assertStatus(400);

        $this->request('POST', '/api/activity', array_fill(0, 8, ['date' => date('Y-m-d'), 'activeKcal' => 1]), $pat);
        $this->assertStatus(400);

        self::assertCount(0, $this->em()->getRepository(ActivityEnergy::class)->findAll());
    }
}
