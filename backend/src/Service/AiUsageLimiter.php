<?php

namespace App\Service;

use App\Entity\Security\User;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Denní limit volání OpenAI na uživatele (ochrana proti zneužití kreditu, hlavně po otevření registrace).
 *
 * Počítadlo je v cache (cache.app), ne v DB – nepotřebuje migraci a jako měkký limit stačí.
 * Smazání cache (např. /ops/cache-clear) počítadla vynuluje.
 */
class AiUsageLimiter
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly int $dailyLimit,
    ) {
    }

    public function getDailyLimit(): int
    {
        return $this->dailyLimit;
    }

    /**
     * Započítá jedno volání. Vrací false, pokud už je dnešní limit vyčerpaný (volání se pak nemá provést).
     */
    public function tryConsume(User $user): bool
    {
        $item = $this->cache->getItem(sprintf('ai_usage.%d.%s', $user->getId(), date('Y-m-d')));
        $used = $item->isHit() ? (int) $item->get() : 0;

        if ($used >= $this->dailyLimit) {
            return false;
        }

        $item->set($used + 1);
        $item->expiresAfter(2 * 24 * 3600);
        $this->cache->save($item);

        return true;
    }
}
