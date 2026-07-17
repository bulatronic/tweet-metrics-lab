<?php

declare(strict_types=1);

namespace App\Tweet\Application;

/**
 * Port for feed cache hit/miss Prometheus counters (implemented in Infrastructure).
 */
interface FeedCacheMetricsInterface
{
    public function incrementHits(): void;

    public function incrementMisses(): void;
}
