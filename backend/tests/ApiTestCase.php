<?php

namespace App\Tests;

use App\Entity\ActivityEnergy;
use App\Entity\DietDiary;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Základ testů API: každý test má prázdnou databázi (SQLite v paměti, schéma z entit) i prázdnou cache
 * (počítadla přihlášení, registrací a denního limitu AI). OpenAI nahrazuje Support\FakeOpenAiClient.
 *
 * Databáze v paměti žije jen s jedním spojením – proto disableReboot(): celý test běží v jednom kernelu.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected const PASSWORD = 'tajne-heslo-123';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Jeden kernel pro celý test – entity vytvořené v testu jsou stejné objekty, jaké vidí kontrolery
        $this->client->disableReboot();

        $em = $this->em();
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());

        static::getContainer()->get('cache.app')->clear();
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(string $email = 'jana@example.com', string $accountType = 'free'): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setAccountType($accountType);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    /**
     * Nastavení uživatele, $values = název setteru bez „set“ => hodnota (např. ['BmrKcal' => 1600]).
     */
    protected function configureSettings(User $user, array $values): void
    {
        $settings = $user->getNewOrExistSettings();
        foreach ($values as $name => $value) {
            $settings->{'set'.$name}($value);
        }
        $user->setSettings($settings);

        $this->em()->persist($settings);
        $this->em()->flush();
    }

    protected function addEntry(User $user, string $date, int $kcal, int $waterInMl = 0): DietDiary
    {
        $entry = (new DietDiary())
            ->setUser($user)
            ->setContent('jídlo')
            ->setKcal($kcal)
            ->setWaterInMl($waterInMl)
            ->setEntryDate(new \DateTimeImmutable($date))
            ->setCreatedAt(new \DateTime());

        $this->em()->persist($entry);
        $this->em()->flush();

        return $entry;
    }

    protected function addActivity(User $user, string $date, string $source, int $activeKcal): void
    {
        $this->em()->persist(new ActivityEnergy($user, new \DateTimeImmutable($date), $source, $activeKcal));
        $this->em()->flush();
    }

    protected function jwt(User $user): string
    {
        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }

    /**
     * JSON požadavek na API. $token = JWT (nebo osobní token cc_pat_…), null = bez přihlášení.
     *
     * @return array<mixed>|null dekódovaná odpověď
     */
    protected function request(string $method, string $uri, ?array $body = null, ?string $token = null): ?array
    {
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($token !== null) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        // Jako v provozu: každý požadavek načítá data z DB znovu (žádné entity v paměti z předchozího kroku)
        $this->em()->clear();
        $this->client->request($method, $uri, server: $headers, content: $body !== null ? json_encode($body) : null);

        $content = $this->client->getResponse()->getContent();

        return $content !== '' && $content !== false ? json_decode($content, true) : null;
    }

    protected function assertStatus(int $expected): void
    {
        $response = $this->client->getResponse();
        self::assertSame($expected, $response->getStatusCode(), (string) $response->getContent());
    }
}
