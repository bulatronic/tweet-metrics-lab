<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Stores blacklisted JWT jti values in Symfony Cache (RedisAdapter) with TTL = remaining token lifetime.
 */
final readonly class JwtTokenBlacklist
{
    private const string KEY_PREFIX = 'jwt_blacklist_';

    public function __construct(
        #[Autowire(service: 'cache.jwt_blacklist')]
        private CacheInterface $cache,
    ) {
    }

    public function add(string $jti, int $ttlSeconds): void
    {
        if ('' === $jti || $ttlSeconds <= 0) {
            return;
        }

        $item = $this->cache->getItem(self::KEY_PREFIX.$jti);
        $item->set(true);
        $item->expiresAfter($ttlSeconds);
        $this->cache->save($item);
    }

    public function contains(string $jti): bool
    {
        if ('' === $jti) {
            return false;
        }

        return $this->cache->hasItem(self::KEY_PREFIX.$jti);
    }
}
