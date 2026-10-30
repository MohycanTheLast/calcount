<?php

namespace App\Tests\Api;

use App\Controller\RegistrationController;
use App\Entity\Security\User;
use App\Tests\ApiTestCase;

/**
 * Přihlášení, omezení pokusů, registrace a jednotný formát chyb mimo kontrolery (401, 403, 404, 405).
 */
class AuthTest extends ApiTestCase
{
    public function testLoginReturnsTokens(): void
    {
        $this->createUser('jana@example.com');

        $data = $this->request('POST', '/api/login', ['email' => 'jana@example.com', 'password' => self::PASSWORD]);

        $this->assertStatus(200);
        self::assertNotEmpty($data['access_token']);
        self::assertNotEmpty($data['refresh_token']);

        // Vydaný token platí pro API
        $me = $this->request('GET', '/api/user/me', token: $data['access_token']);
        $this->assertStatus(200);
        self::assertSame('jana@example.com', $me['user_identifier']);
    }

    public function testWrongPassword(): void
    {
        $this->createUser('jana@example.com');

        $data = $this->request('POST', '/api/login', ['email' => 'jana@example.com', 'password' => 'spatne-heslo']);

        $this->assertStatus(401);
        self::assertSame(['error' => 'Invalid credentials'], $data);
    }

    public function testLoginIsThrottledAfterFiveFailures(): void
    {
        $this->createUser('jana@example.com');

        for ($i = 0; $i < 5; $i++) {
            $this->request('POST', '/api/login', ['email' => 'jana@example.com', 'password' => 'spatne-heslo']);
            $this->assertStatus(401);
        }

        // Zablokované je i správné heslo – jinak by šlo hádat dál
        $data = $this->request('POST', '/api/login', ['email' => 'jana@example.com', 'password' => self::PASSWORD]);
        $this->assertStatus(429);
        self::assertStringContainsString('Příliš mnoho', $data['error']);

        // Jiný účet ze stejné IP blokovaný není
        $this->createUser('petr@example.com');
        $this->request('POST', '/api/login', ['email' => 'petr@example.com', 'password' => self::PASSWORD]);
        $this->assertStatus(200);
    }

    public function testLoginValidation(): void
    {
        $data = $this->request('POST', '/api/login', ['email' => 'jana@example.com']);

        $this->assertStatus(422);
        self::assertSame('password', $data['violations'][0]['field']);
    }

    public function testAiDailyLimit(): void
    {
        // AI_DAILY_LIMIT=3 v .env.test
        $token = $this->jwt($this->createUser());

        for ($i = 0; $i < 3; $i++) {
            $this->request('POST', '/api/calculate', ['food' => 'jablko'], $token);
            $this->assertStatus(200);
        }

        $data = $this->request('POST', '/api/calculate', ['food' => 'jablko'], $token);
        $this->assertStatus(429);
        self::assertStringContainsString('limit 3', $data['error']);

        // Limit je na uživatele
        $this->request('POST', '/api/calculate', ['food' => 'jablko'], $this->jwt($this->createUser('petr@example.com')));
        $this->assertStatus(200);
    }

    public function testRegister(): void
    {
        $data = $this->request('POST', '/api/register', [
            'email' => '  Nova@Example.com ',
            'password' => 'dlouhe-heslo',
            'termsAccepted' => true,
        ]);

        $this->assertStatus(201);
        self::assertSame(['status' => 'registered'], $data);

        $user = $this->em()->getRepository(User::class)->findOneBy(['email' => 'nova@example.com']);
        self::assertNotNull($user);
        self::assertSame(RegistrationController::TERMS_VERSION, $user->getTermsVersion());
        self::assertSame('free', $user->getAccountType());

        $this->request('POST', '/api/login', ['email' => 'nova@example.com', 'password' => 'dlouhe-heslo']);
        $this->assertStatus(200);
    }

    public function testRegisterExistingEmail(): void
    {
        $this->createUser('jana@example.com');

        $data = $this->request('POST', '/api/register', [
            'email' => 'jana@example.com',
            'password' => 'dlouhe-heslo',
            'termsAccepted' => true,
        ]);

        $this->assertStatus(409);
        self::assertArrayHasKey('error', $data);
    }

    public function testRegisterValidation(): void
    {
        $data = $this->request('POST', '/api/register', ['email' => 'neni-email', 'password' => 'kratke']);

        $this->assertStatus(422);
        self::assertSame(
            [
                ['field' => 'email', 'message' => 'Zadej platný e-mail.'],
                ['field' => 'password', 'message' => 'Heslo musí mít aspoň 8 znaků.'],
                ['field' => 'termsAccepted', 'message' => 'Pro registraci je potřeba souhlasit s podmínkami užívání.'],
            ],
            $data['violations']
        );
        self::assertSame('Zadej platný e-mail.', $data['error']);
        self::assertCount(0, $this->em()->getRepository(User::class)->findAll());
    }

    public function testRegistrationIsThrottledPerIp(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->request('POST', '/api/register', ['email' => "u$i@example.com", 'password' => 'dlouhe-heslo', 'termsAccepted' => true]);
            $this->assertStatus(201);
        }

        $this->request('POST', '/api/register', ['email' => 'u6@example.com', 'password' => 'dlouhe-heslo', 'termsAccepted' => true]);
        $this->assertStatus(429);
    }

    public function testUnifiedErrorsOutsideControllers(): void
    {
        // Bez tokenu
        self::assertSame(['error' => 'JWT Token not found'], $this->request('GET', '/api/days'));
        $this->assertStatus(401);

        // Neplatný token
        self::assertArrayHasKey('error', $this->request('GET', '/api/days', token: 'neplatny'));
        $this->assertStatus(401);

        $token = $this->jwt($this->createUser());

        self::assertSame(['error' => 'Not Found'], $this->request('GET', '/api/neexistuje', token: $token));
        $this->assertStatus(404);

        self::assertSame(['error' => 'Method Not Allowed'], $this->request('PUT', '/api/calculate', token: $token));
        $this->assertStatus(405);

        // Neplatný JSON
        $this->client->request('POST', '/api/calculate', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token], content: '{nope');
        $this->assertStatus(400);
        self::assertSame(['error' => 'Bad Request'], json_decode($this->client->getResponse()->getContent(), true));
    }
}
