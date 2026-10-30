<?php

namespace App\Tests\Api;

use App\Entity\DietDiary;
use App\Tests\ApiTestCase;
use App\Tests\Support\FakeOpenAiClient;

/**
 * Záznamy deníku: vytvoření přes AI, úprava a smazání jen vlastníkem, jednotný formát chyb.
 */
class DiaryEntriesTest extends ApiTestCase
{
    public function testCreateEntryStoresAiEstimateForToday(): void
    {
        $token = $this->jwt($this->createUser());

        $data = $this->request('POST', '/api/calculate', ['food' => 'rohlík s máslem'], $token);

        $this->assertStatus(200);
        self::assertSame(FakeOpenAiClient::KCAL, (int) $data['kcal']);

        $entry = $this->em()->find(DietDiary::class, $data['id']);
        self::assertSame('rohlík s máslem', $entry->getContent());
        self::assertSame(date('Y-m-d'), $entry->getEntryDate()->format('Y-m-d'));
        self::assertSame(10, $entry->getProteinG());
    }

    public function testCreateEntryForPastDay(): void
    {
        $token = $this->jwt($this->createUser());
        $date = (new \DateTimeImmutable('-3 days'))->format('Y-m-d');

        $data = $this->request('POST', '/api/calculate', ['food' => 'polévka', 'date' => $date], $token);

        $this->assertStatus(200);
        self::assertSame($date, $this->em()->find(DietDiary::class, $data['id'])->getEntryDate()->format('Y-m-d'));
    }

    public function testCreateEntryValidation(): void
    {
        $token = $this->jwt($this->createUser());

        $data = $this->request('POST', '/api/calculate', ['food' => '   '], $token);
        $this->assertStatus(422);
        self::assertSame('food', $data['violations'][0]['field']);
        self::assertSame($data['violations'][0]['message'], $data['error']);

        $data = $this->request('POST', '/api/calculate', ['food' => str_repeat('a', 501)], $token);
        $this->assertStatus(422);
        self::assertStringContainsString('příliš dlouhý', $data['error']);

        $data = $this->request('POST', '/api/calculate', ['food' => 'chleba', 'date' => '2001-01-01'], $token);
        $this->assertStatus(422);
        self::assertSame('date', $data['violations'][0]['field']);

        $this->request('POST', '/api/calculate', ['food' => 'chleba', 'date' => '2026-13-45'], $token);
        $this->assertStatus(422);

        self::assertCount(0, $this->em()->getRepository(DietDiary::class)->findAll());
    }

    public function testOwnerCanUpdateAndDeleteEntry(): void
    {
        $token = $this->jwt($this->createUser());
        $id = $this->request('POST', '/api/calculate', ['food' => 'jablko'], $token)['id'];

        $this->request('PATCH', "/api/diary-entries/$id", ['kcal' => 95.6, 'waterInMl' => 150], $token);
        $this->assertStatus(204);

        $entry = $this->em()->find(DietDiary::class, $id);
        self::assertSame(96, $entry->getKcal());
        self::assertSame(150, $entry->getWaterInMl());

        // Neposlaná hodnota se nemění
        $this->request('PATCH', "/api/diary-entries/$id", ['kcal' => 80], $token);
        $this->assertStatus(204);
        $entry = $this->em()->find(DietDiary::class, $id);
        self::assertSame(80, $entry->getKcal());
        self::assertSame(150, $entry->getWaterInMl());

        $this->request('DELETE', "/api/diary-entries/$id", token: $token);
        $this->assertStatus(204);
        $this->em()->clear();
        self::assertNull($this->em()->find(DietDiary::class, $id));
    }

    public function testUpdateValidation(): void
    {
        $token = $this->jwt($this->createUser());
        $id = $this->request('POST', '/api/calculate', ['food' => 'jablko'], $token)['id'];

        $data = $this->request('PATCH', "/api/diary-entries/$id", ['kcal' => -5], $token);

        $this->assertStatus(422);
        self::assertSame([['field' => 'kcal', 'message' => 'Kalorie musí být od 0 do 20000.']], $data['violations']);
    }

    public function testForeignEntryLooksLikeNotFound(): void
    {
        $ownerToken = $this->jwt($this->createUser('jana@example.com'));
        $id = $this->request('POST', '/api/calculate', ['food' => 'jablko'], $ownerToken)['id'];

        $otherToken = $this->jwt($this->createUser('petr@example.com'));

        $data = $this->request('PATCH', "/api/diary-entries/$id", ['kcal' => 1], $otherToken);
        $this->assertStatus(404);
        self::assertSame(['error' => 'Not Found'], $data);

        $this->request('DELETE', "/api/diary-entries/$id", token: $otherToken);
        $this->assertStatus(404);

        // Cizí záznam se nevrací ani v seznamu dne
        self::assertSame([], $this->request('GET', '/api/diary-day/'.date('Y-m-d'), token: $otherToken));

        self::assertSame(FakeOpenAiClient::KCAL, $this->em()->find(DietDiary::class, $id)->getKcal());
    }

    public function testNonexistentEntryReturnsUnifiedNotFound(): void
    {
        $token = $this->jwt($this->createUser());

        $data = $this->request('DELETE', '/api/diary-entries/999', token: $token);

        $this->assertStatus(404);
        self::assertSame(['error' => 'Not Found'], $data);
    }

    public function testLegacyRoutesStillWork(): void
    {
        $token = $this->jwt($this->createUser());
        $id = $this->request('POST', '/api/calculate', ['food' => 'jablko'], $token)['id'];

        $this->request('POST', "/api/save/$id", ['kcal' => 70, 'waterInMl' => 0], $token);
        $this->assertStatus(204);
        self::assertSame(70, $this->em()->find(DietDiary::class, $id)->getKcal());

        $this->request('POST', "/api/delete/$id", token: $token);
        $this->assertStatus(204);
    }
}
