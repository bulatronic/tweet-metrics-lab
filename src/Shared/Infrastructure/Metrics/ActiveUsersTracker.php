<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Tracks authenticated users via Symfony Cache (RedisAdapter).
 *
 * Per request: cache item active_user_{id} with TTL 5m + membership in an index item.
 * Console command prunes expired members and returns the count (same role as former SCARD).
 */
final readonly class ActiveUsersTracker
{
    private const string USER_KEY_PREFIX = 'active_user_';
    private const string INDEX_KEY = 'active_users_index';
    public const int TTL_SECONDS = 300;

    public function __construct(
        #[Autowire(service: 'cache.active_users')]
        private CacheInterface $cache,
    ) {
    }

    public function touch(string $userId): void
    {
        if ('' === $userId) {
            return;
        }

        $userItem = $this->cache->getItem(self::USER_KEY_PREFIX.$userId);
        $userItem->set(true);
        $userItem->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($userItem);

        $indexItem = $this->cache->getItem(self::INDEX_KEY);
        /** @var array<string, true> $ids */
        $ids = $indexItem->isHit() && \is_array($indexItem->get())
            ? $indexItem->get()
            : [];
        $ids[$userId] = true;
        $indexItem->set($ids);
        $this->cache->save($indexItem);
    }

    /**
     * Removes stale index members whose TTL items expired, then returns active count.
     */
    public function countActive(): int
    {
        $indexItem = $this->cache->getItem(self::INDEX_KEY);
        if (!$indexItem->isHit() || !\is_array($indexItem->get())) {
            return 0;
        }

        /** @var array<string, mixed> $ids */
        $ids = $indexItem->get();
        $active = [];
        foreach (array_keys($ids) as $userId) {
            $userId = (string) $userId;
            if ($this->cache->hasItem(self::USER_KEY_PREFIX.$userId)) {
                $active[$userId] = true;
            }
        }

        $indexItem->set($active);
        $this->cache->save($indexItem);

        return \count($active);
    }
}
