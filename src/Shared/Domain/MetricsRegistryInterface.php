<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Domain-facing metrics port (business counters/gauges).
 * Infrastructure implements this via promphp CollectorRegistry.
 */
interface MetricsRegistryInterface
{
    public function incrementTweetsCreated(): void;

    public function incrementLikes(): void;

    public function incrementUnlikes(): void;

    public function incrementFollows(): void;

    public function incrementUnfollows(): void;

    public function setActiveUsers5m(int $count): void;
}
