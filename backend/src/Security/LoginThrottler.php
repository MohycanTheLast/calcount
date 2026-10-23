<?php

namespace App\Security;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Omezení neúspěšných pokusů o přihlášení heslem (ochrana proti hádání hesel).
 *
 * - max. 5 neúspěšných pokusů na kombinaci e-mail + IP za 15 minut,
 * - max. 30 neúspěšných pokusů z jedné IP za 15 minut (zkoušení mnoha e-mailů).
 * Okno se počítá od prvního neúspěšného pokusu. Počítadla jsou v cache (cache.app), stejně jako AiUsageLimiter.
 */
class LoginThrottler
{
    private const WINDOW_SECONDS = 900;
    private const MAX_FAILURES_PER_ACCOUNT = 5;
    private const MAX_FAILURES_PER_IP = 30;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function isBlocked(string $email, ?string $ip): bool
    {
        return $this->failures($this->accountKey($email, $ip)) >= self::MAX_FAILURES_PER_ACCOUNT
            || $this->failures($this->ipKey($ip)) >= self::MAX_FAILURES_PER_IP;
    }

    public function registerFailure(string $email, ?string $ip): void
    {
        $this->increment($this->accountKey($email, $ip));
        $this->increment($this->ipKey($ip));
    }

    public function reset(string $email, ?string $ip): void
    {
        $this->cache->deleteItem($this->accountKey($email, $ip));
    }

    public function getWindowMinutes(): int
    {
        return intdiv(self::WINDOW_SECONDS, 60);
    }

    private function failures(string $key): int
    {
        $item = $this->cache->getItem($key);

        return $item->isHit() ? (int) $item->get()['count'] : 0;
    }

    private function increment(string $key): void
    {
        $item = $this->cache->getItem($key);
        $data = $item->isHit() ? $item->get() : ['count' => 0, 'expiresAt' => time() + self::WINDOW_SECONDS];
        $data['count']++;

        // Expirace zůstává od prvního pokusu – další pokusy okno neprodlužují
        $item->set($data);
        $item->expiresAt((new \DateTimeImmutable())->setTimestamp($data['expiresAt']));
        $this->cache->save($item);
    }

    private function accountKey(string $email, ?string $ip): string
    {
        return 'login_fail.account.'.sha1(mb_strtolower(trim($email)).'|'.$ip);
    }

    private function ipKey(?string $ip): string
    {
        return 'login_fail.ip.'.sha1((string) $ip);
    }
}
