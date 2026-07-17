<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

/**
 * Stores blacklisted JWT jti values in Redis with TTL = remaining token lifetime.
 * Uses one Redis key per jti (SET + EXPIRE): Redis SET members cannot have individual TTLs.
 */
final readonly class JwtTokenBlacklist
{
    private const string KEY_PREFIX = 'jwt_blacklist:';

    public function __construct(
        private \Redis $redis,
    ) {
    }

    public function add(string $jti, int $ttlSeconds): void
    {
        if ('' === $jti || $ttlSeconds <= 0) {
            return;
        }

        $this->redis->setex(self::KEY_PREFIX.$jti, $ttlSeconds, '1');
    }

    public function contains(string $jti): bool
    {
        if ('' === $jti) {
            return false;
        }

        return $this->redis->exists(self::KEY_PREFIX.$jti) > 0;
    }
}
