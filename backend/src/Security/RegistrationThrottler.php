<?php

namespace App\Security;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Omezení počtu registrací z jedné IP (ochrana proti hromadnému zakládání účtů – každý účet má vlastní
 * denní limit AI, takže by se jinak dal obejít).
 *
 * Max. 5 pokusů o registraci z jedné IP za hodinu, okno od prvního pokusu. Počítadla jsou v cache (cache.app),
 * stejně jako LoginThrottler.
 */
class RegistrationThrottler
{
    private const WINDOW_SECONDS = 3600;
    private const MAX_ATTEMPTS_PER_IP = 5;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function isBlocked(?string $ip): bool
    {
        $item = $this->cache->getItem($this->key($ip));

        return $item->isHit() && (int) $item->get()['count'] >= self::MAX_ATTEMPTS_PER_IP;
    }

    public function registerAttempt(?string $ip): void
    {
        $item = $this->cache->getItem($this->key($ip));
        $data = $item->isHit() ? $item->get() : ['count' => 0, 'expiresAt' => time() + self::WINDOW_SECONDS];
        $data['count']++;

        // Expirace zůstává od prvního pokusu – další pokusy okno neprodlužují
        $item->set($data);
        $item->expiresAt((new \DateTimeImmutable())->setTimestamp($data['expiresAt']));
        $this->cache->save($item);
    }

    public function getWindowMinutes(): int
    {
        return intdiv(self::WINDOW_SECONDS, 60);
    }

    private function key(?string $ip): string
    {
        return 'register.ip.'.sha1((string) $ip);
    }
}
